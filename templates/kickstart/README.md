# subrion-template-kickstart
Default template for Subrion CMS 4.x with style support for every Subrion plugin and package.

## Installation
1. Download latest release https://github.com/intelliants/subrion-template-kickstart/releases
2. Unpack contents of archive to `[root]/templates` folder
3. Rename `subrion-template-kickstart-master` to `kickstart`
4. Activate template in admin dashboard.

Or you can
```
cd [root]/templates # where root is the location of subrion core
git clone https://github.com/intelliants/subrion-template-kickstart.git ./kickstart
```
Voila! Just activate it in your Dashboard / Extensions / Templates

## Development

> This manual covers development of visual styles. To learn more about Subrion template structure and configuration options, please refer to Subrion Extensions wiki page: `URL_TO_SUBRION_WIKI`

The Kickstart template is setup to use [Gulp](http://gulpjs.com/) to compile LESS (with source maps), run it through [AutoPrefixr](https://github.com/postcss/autoprefixer), optimize images, and syncs changes across local development devices with [BrowserSync](https://browsersync.io/docs/gulp/), with flexibility to add any additional tasks via the Gulpfile. Alternatively, you can use [Prepros](https://prepros.io/) or whatever else y ou prefer to compile the LESS and manage the JavaScript.

### Prerequisites

In root folder of Kickstart template run:
```
npm i
```
To install all necessary node modules.

Before running `dev` task, be sure to change local proxy config in **package.json** file for BrowserSync to work properly.

### Tasks

```
gulp dev
```
Starts BrowserSync and watches for LESS files. Compiles LESS with SourceMaps.

```
gulp build
```
Compiles LESS and optimizes images.

### Template styles/elements page

There is a hidden page with all supported elements. You can access it like this:
```
http://yourdomain.com/?elements
```

## Contributing

Anyone is welcome to contribute! Check out the [Contributing Guidelines](CONTRIBUTING.md).