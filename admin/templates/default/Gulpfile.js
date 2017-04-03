//
// Please don't forget to do `gulp build` before 
// pushing to repo
// --------------------------------------------------


var pjson      = require('./package.json'),
    gulp       = require("gulp"),
    path       = require('path'),
    gutil      = require('gulp-util'),
    notify     = require("gulp-notify"),
    concat     = require("gulp-concat"),
    rename     = require("gulp-rename"),
    imagemin   = require("gulp-imagemin"),
    less       = require("gulp-less"),
    sourcemaps = require('gulp-sourcemaps'),
    cleanCSS   = require('gulp-clean-css');

var config = {
    paths: {
        images: {
            src:  ["img/**/*.jpg", "img/**/*.jpeg", "img/**/*.png"],
            dest: "img"
        },
        less: {
            path: "less/**/*.less",
            src: [
                "less/base-calmy.less",
                "less/base-darkness.less",
                "less/base-default.less",
                "less/base-gebeus-waterfall.less",
                "less/base-radiant-orchid.less",
                "less/base-roseus.less"
            ],
            srcDev: "less/base-default.less",
            dest: "css"
        }
    }
};

gulp.task("images", function(){
    return gulp.src(config.paths.images.src)
        .pipe(imagemin({
            progressive: true,
            interlaced: true
        }))
        .pipe(gulp.dest(config.paths.images.dest));
});

gulp.task("less", function(){
    return gulp.src(config.paths.less.src)
        .pipe(less().on('error', function(err) {
            gutil.log(err);
            this.emit('end');
        }))
        .pipe(cleanCSS({
            advanced: false
        }))
        .pipe(rename(function (path) {
            path.basename = path.basename.replace('base', 'bootstrap');
        }))
        .pipe(gulp.dest(config.paths.less.dest))
        .pipe(notify({
            sound: true,
            title: "Build completed!",
            message: "File: <%= file.relative %>. No errors. Images optimized",
            icon: path.join(__dirname, "img/ico/apple-touch-icon.png")
        }));
});

gulp.task("less-dev", function(){
    return gulp.src(config.paths.less.srcDev)
        .pipe(sourcemaps.init())
        .pipe(less().on('error', function(err) {
            gutil.log(err);
            this.emit('end');
        }))
        .pipe(sourcemaps.write())
        .pipe(rename(function (path) {
            path.basename = path.basename.replace('base', 'bootstrap');
        }))
        .pipe(gulp.dest(config.paths.less.dest))
        .pipe(notify({
            sound: true,
            title: "Compilation done! =)",
            message: "File: <%= file.relative %>. No errors.",
            icon: path.join(__dirname, "img/ico/apple-touch-icon.png")
        }));
});

gulp.task("build", ["less", "images"]);

gulp.task("watch", function(){
    gulp.watch(config.paths.less.path, ["less"]);
});

gulp.task("watch-dev", function(){
    gulp.watch(config.paths.less.path, ["less-dev"]);
});

gulp.task("default", function() {
    console.log('Silence is gold');
});