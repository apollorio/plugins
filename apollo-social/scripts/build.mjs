import { build } from 'esbuild';
import pc from 'picocolors';
import { fileURLToPath } from 'url';
import path from 'path';
import { access, writeFile } from 'fs/promises';
import { watch, existsSync } from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');

const assetTargets = [
  'assets/js/builder.js',
  'assets/js/canvas.js',
  'assets/js/canvas-mode.js',
  'assets/js/apollo-mod.js',
  'assets/js/integra.js',
  'assets/js/widgets.js',
  'assets/js/admin/analytics.js',
  'public/service-worker.js'
];

async function ensureExists(relativePath) {
  const absPath = path.join(projectRoot, relativePath);
  try {
    await access(absPath);
    return absPath;
  } catch (error) {
    return null;
  }
}

async function compileFile(relativePath) {
  const absPath = await ensureExists(relativePath);
  if (!absPath) {
    console.warn(pc.yellow(`⚠︎ Skipping missing asset: ${relativePath}`));
    return;
  }

  const result = await build({
    entryPoints: [absPath],
    outfile: absPath,
    bundle: false,
    write: false,
    minify: true,
    minifySyntax: true,
    minifyWhitespace: true,
    minifyIdentifiers: false,
    legalComments: 'none',
    target: 'es2019'
  });

  const output = result.outputFiles?.[0];
  if (!output) {
    console.warn(pc.yellow(`⚠︎ esbuild did not return output for ${relativePath}`));
    return;
  }

  await writeFile(absPath, Buffer.concat([output.contents, Buffer.from('\n')]));
  console.log(pc.green(`✔ Built ${relativePath} (${output.contents.length} bytes)`));
}

async function runBuild() {
  console.log(pc.cyan('Running Apollo Social front-end build…'));
  for (const file of assetTargets) {
    await compileFile(file);
  }
  console.log(pc.cyan('Build finished.'));
}

function enableWatchMode() {
  console.log(pc.magenta('Watch mode enabled. Listening for changes…'));

  const resolvedTargets = assetTargets
    .map((relative) => ({
      relative,
      absolute: path.join(projectRoot, relative)
    }))
    .filter(({ absolute }) => {
      if (existsSync(absolute)) {
        return true;
      }

      console.warn(pc.yellow(`⚠︎ Cannot watch missing asset: ${absolute}`));
      return false;
    });

  for (const { relative, absolute } of resolvedTargets) {
    watch(absolute, { persistent: true }, async (eventType) => {
      if (eventType === 'change') {
        console.log(pc.blue(`↺ Detected change in ${relative}. Rebuilding…`));
        try {
          await compileFile(relative);
        } catch (error) {
          console.error(pc.red(`Build failed for ${relative}`));
          console.error(error);
        }
      }
    });
  }
}

(async () => {
  const watchFlag = process.argv.includes('--watch');
  try {
    await runBuild();
    if (watchFlag) {
      enableWatchMode();
    }
  } catch (error) {
    console.error(pc.red('Build failed.'));
    console.error(error);
    process.exitCode = 1;
  }
})();
