const path = require('path');
const { generateWpEnvConfig, loadEnv } = require('@gravitykit/e2e-bootstrap');

// Explicit load — INIT_CWD unreliable via npm run on some machines (see Tooling/.claude/e2e-bootstrap-migration.md)
loadEnv(path.resolve(__dirname, '../../../.env'));

generateWpEnvConfig({
	outputDir: __dirname,
	pluginPath: '../../..', // Relative path from this file to the plugin root.
	additionalLifecycleCommands: [],
}).catch((err) => {
	console.error(err);
	process.exit(1);
});
