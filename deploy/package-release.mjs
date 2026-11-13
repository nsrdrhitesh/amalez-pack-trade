import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';

const source = fs.realpathSync(process.argv[2] || '.');
const output = path.resolve(process.argv[3] || '');
const commit = process.argv[4] || '';
if (!output.endsWith('.tar.gz') || !/^[a-f0-9]{40}$/.test(commit)) throw Error('Usage: node deploy/package-release.mjs SOURCE OUTPUT.tar.gz COMMIT_SHA');
for (const file of ['vendor/autoload.php', 'public/build/manifest.json', 'deploy/production.env.example']) {
  if (!fs.existsSync(path.join(source, file))) throw Error('Missing runtime input: '+file);
}
if (fs.existsSync(path.join(source,'vendor/phpunit'))) throw Error('Install production dependencies with --no-dev before packaging.');
if (fs.existsSync(output)) throw Error('Artifact exists; choose a fresh output path.');
const marker = path.join(source,'public/release.json');
if (fs.existsSync(marker)) throw Error('Release marker already exists; inspect before reuse.');
fs.writeFileSync(marker, JSON.stringify({commit,built_at:new Date().toISOString()},null,2)+'\n');
fs.mkdirSync(path.dirname(output),{recursive:true});
const excludes = ['.git','.github','.tooling','node_modules','storage','tests','docs','scripts','resources/images/source','bootstrap/cache/*','public/hot','.env*','auth.json','credentials*.json','*.key','*.pem','*.pfx','*.p12','*.sql','*.sqlite*','*.log'];
const archive = spawnSync('tar',['-czf',output,...excludes.map(item=>'--exclude='+item),'-C',source,'.'],{stdio:'inherit'});
if (archive.status !== 0) throw Error('Native archive creation failed.');
const entries = spawnSync('tar',['-tzf',output],{encoding:'utf8',maxBuffer:16*1024*1024});
if (entries.status !== 0) throw Error('Archive verification failed.');
const files=entries.stdout.split('\n').filter(Boolean);
for(const file of files){
  const relative=file.replace(/^\.\//,'');
  if(relative.startsWith('/') || relative.split('/').includes('..') || /(^|\/)\.env[^/]*$/.test(relative) || relative.startsWith('.git/')) throw Error('Unsafe archive entry detected.');
}
const sha256=crypto.createHash('sha256').update(fs.readFileSync(output)).digest('hex');
console.log(JSON.stringify({files:files.length,bytes:fs.statSync(output).size,sha256,commit},null,2));
