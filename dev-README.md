# MarketPress eCommerce

All development for MarketPress should be done in the `development` branch. Please fork and submit pull-requests to the `development` branch only!

## Start development

1. Install node, `nvm` can be used to switch between node versions.
2. In marketpress folder run `npm install` to install all needed packages in the `node_modules` folder.
3. Execute `npm run watch` to start watching changes to CSS and JS files.

## Npm run options
`npm run watch` - Watch for CSS and JS changes

`npm run release` - Prepare CSS, JS and POT files for release

`npm run build` - Build packages for both Pro and Free versions (zip files can be found in `build` directory)
