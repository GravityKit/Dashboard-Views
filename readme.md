### Installation Instructions

To install the plugin, download [the latest release](https://github.com/GravityKit/Dashboard-Views/releases) to your WordPress plugins folder and then activate it.

### For Developers

If you wish to make changes to the plugin, you need to install the necessary dependencies and compile assets. First, a couple of prerequisites:

1. Make sure that you have the full plugin source code by either cloning this repo or downloading the source code (not the versioned release) from the [releases section](https://github.com/GravityKit/Dashboard-Views/releases).

2. Install [Composer](https://getcomposer.org/)

3. Install [Node.js](https://nodejs.org/en/)
    - We recommend a Node.js version manager [for Linux/macOS](https://github.com/nvm-sh/nvm) or [Windows](https://github.com/coreybutler/nvm-windows)
    - Run `npm install -g grunt-cli` if this the first time you've installed Node.js or switched to a new version

Next, install dependencies:
1. Run `composer install` to install Composer dependencies

2. Run `npm install` to install Node.js dependencies

To compile/minify UI assets, run `grunt`.

You do not have to run the commands if submitting a pull request as the minification process is handled by our CI/CD pipeline.
