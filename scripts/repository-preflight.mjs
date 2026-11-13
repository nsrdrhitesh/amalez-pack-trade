import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

// Audit the exact prospective Git file set without initializing or staging
// the real project. No secret values or matching lines are printed.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'amalez-git-audit-'));
execFileSync('git', ['init', '--quiet', scratch]);
const gitArgs = ['--git-dir', path.join(scratch, '.git'), '--work-tree', root];
const git = args => execFileSync('git', [...gitArgs, ...args], { cwd: root, encoding: 'utf8' });
const files = git(['ls-files', '--others', '--exclude-standard', '-z']).split('\0').filter(Boolean);
const rules = [
  ['private-key', /-----BEGIN (?:RSA |EC |OPENSSH |DSA |ENCRYPTED )?PRIVATE KEY-----/g],
  ['github-token', /\b(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{30,})\b/g],
  ['aws-access-key', /\b(?:AKIA|ASIA)[A-Z0-9]{16}\b/g],
  ['google-api-key', /\bAIza[0-9A-Za-z_-]{30,}\b/g],
  ['slack-token', /\bxox[baprs]-[0-9A-Za-z-]{15,}\b/g],
  ['stripe-secret', /\b(?:sk|rk)_live_[0-9A-Za-z]{16,}\b/g],
  ['credential-in-url', /https?:\/\/[^\s/:"'<>]+:[^\s/@"'<>]+@[^\s"'<>]+/g],
  ['assigned-secret', /(?:password|passwd|secret|api[_-]?key|access[_-]?token|client[_-]?secret)\s*[=:]\s*["'][A-Za-z0-9+/=_-]{20,}["']/gi],
];
const findings = [];
// Also compare candidates against actual non-empty local secret values, while
// keeping those values in memory and reporting only a key name on a match.
const localSecrets = [];
const envPath = path.join(root, '.env');
if (fs.existsSync(envPath)) {
  for (const line of fs.readFileSync(envPath, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^([A-Z0-9_]*(?:KEY|SECRET|PASSWORD|TOKEN)[A-Z0-9_]*)=(.*)$/);
    if (!match) continue;
    const value = match[2].trim().replace(/^['"]|['"]$/g, '');
    if (value.length >= 8 && !['null','false','true'].includes(value)) localSecrets.push({key:match[1],value});
  }
}
let totalBytes = 0;
for (const file of files) {
  const buffer = fs.readFileSync(path.join(root, file));
  totalBytes += buffer.length;
  if (/(^|\/)\.env(?:\.|$)/.test(file) && file !== '.env.example') findings.push({file, type:'environment-file'});
  if (/\.(?:pem|key|pfx|p12|sqlite\d*|sql|dump)$/i.test(file)) findings.push({file, type:'sensitive-file-extension'});
  if (/(^|\/)(?:auth\.json|credentials[^/]*\.json|id_rsa|id_ed25519)$/.test(file)) findings.push({file,type:'credential-file'});
  if (buffer.includes(0)) continue;
  const content = buffer.toString('utf8');
  for (const secret of localSecrets) {
    if (content.includes(secret.value)) findings.push({file,type:'local-environment-secret',key:secret.key});
  }
  // Test fixtures deliberately contain fake passwords; still scan them for
  // key blocks and recognizable token formats, excluding generic assignments.
  for (const [type, regex] of rules) {
    if (type === 'assigned-secret' && (file.startsWith('tests/') || file === 'database/factories/UserFactory.php' || file === 'scripts/repository-preflight.mjs')) continue;
    regex.lastIndex = 0;
    for (const match of content.matchAll(regex)) findings.push({file, type, line:content.slice(0, match.index).split('\n').length});
  }
}
const mustIgnore = ['.env','.env.production','.env.local','.env.backup','auth.json','credentials.json','server.pem','private.key','database/backup.sql','database/database.sqlite','storage/logs/laravel.log','storage/framework/sessions/example','bootstrap/cache/config.php','vendor/autoload.php','node_modules/example.js','.tooling/gh.exe'];
const exclusions = mustIgnore.map(file => {
  let ignored = false;
  try { git(['check-ignore', '--no-index', '--quiet', file]); ignored = true; } catch {}
  return {file, ignored};
});
const failedExclusions = exclusions.filter(item => !item.ignored);
console.log(JSON.stringify({root, prospectiveFiles:files.length, totalBytes, comparedLocalSecretValues:localSecrets.length, findings, exclusions, exampleTemplateIncluded:files.includes('.env.example'), largestFiles:files.map(file=>({file,bytes:fs.statSync(path.join(root,file)).size})).sort((a,b)=>b.bytes-a.bytes).slice(0,6)},null,2));
if (findings.length || failedExclusions.length) process.exitCode = 1;
