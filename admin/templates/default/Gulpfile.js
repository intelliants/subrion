var pjson      = require('./package.json'),
    gulp       = require("gulp"),
    gutil      = require('gulp-util'),
    concat     = require("gulp-concat"),
    rename     = require("gulp-rename"),
    imagemin   = require("gulp-imagemin"),
    less       = require("gulp-less"),
    cleanCSS   = require('gulp-clean-css');
    //progeny    = require('gulp-progeny'),
    //cache      = require('gulp-cached'),
    //remember   = require('gulp-remember'),

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
        //.pipe(cache('less'))
        //.pipe(progeny())
        .pipe(less().on('error', function(err) {
            gutil.log(err);
            this.emit('end');
        }))
        .pipe(cleanCSS({
            advanced: false
        }))
        //.pipe(remember('less'))
        .pipe(rename(function (path) {
            path.basename = path.basename.replace('base', 'bootstrap');
        }))
        .pipe(gulp.dest(config.paths.less.dest));
});

gulp.task("less-dev", function(){
    return gulp.src(config.paths.less.srcDev)
        //.pipe(cache('less'))
        //.pipe(progeny())
        .pipe(less().on('error', function(err) {
            gutil.log(err);
            this.emit('end');
        }))
        .pipe(cleanCSS({
            advanced: false
        }))
        //.pipe(remember('less'))
        .pipe(rename(function (path) {
            path.basename = path.basename.replace('base', 'bootstrap');
        }))
        .pipe(gulp.dest(config.paths.less.dest));
});

gulp.task("build", ["less", "images"]);

gulp.task("watch", function(){
    gulp.watch(config.paths.less.path, ["less"]);
});

gulp.task("watch", function(){
    gulp.watch(config.paths.less.path, ["less"]);
});

gulp.task("watch-dev", function(){
    gulp.watch(config.paths.less.path, ["less-dev"]);
});

gulp.task("default", function() {
    console.log('Silence is gold');
});