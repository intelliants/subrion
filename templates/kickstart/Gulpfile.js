var fs           = require("fs"),
    serverConf   = (fs.existsSync('./config.json')) ? require('./config.json').server : null,
    gulp         = require("gulp"),
    gutil        = require('gulp-util'),
    concat       = require("gulp-concat"),
    imagemin     = require("gulp-imagemin"),
    less         = require("gulp-less"),
    sourcemaps   = require('gulp-sourcemaps'),
    cleanCSS     = require('gulp-clean-css'),
    autoprefixer = require('gulp-autoprefixer'),
    browserSync  = require('browser-sync').create();

var config = {
  paths: {
    images: {
      src:  ["img/**/*.jpg", "img/**/*.jpeg", "img/**/*.png"],
      dest: "img"
    },
    less: {
      path: "less/**/*.less",
      src:  [
        "less/iabootstrap.less",
        "less/ckeditor.less"
      ],
      dest: "css"
    }
  }
};

gulp.task("images", function() {
  return gulp.src(config.paths.images.src)
    .pipe(imagemin({
      progressive: true,
      interlaced: true
    }))
    .pipe(gulp.dest(config.paths.images.dest));
});

gulp.task("less-dev", function(){
  return gulp.src(config.paths.less.src)
    .pipe(sourcemaps.init())
    .pipe(less().on('error', function(err) {
        gutil.log(err);
        this.emit('end');
    }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(config.paths.less.dest))
    .pipe(browserSync.stream());
});

gulp.task("less", function() {
  return gulp.src(config.paths.less.src)
    .pipe(less().on('error', function(err) {
      gutil.log(err);
      this.emit('end');
    }))
    .pipe(autoprefixer({
        browsers: ['last 2 versions'],
        cascade: false
    }))
    .pipe(cleanCSS({
      advanced: false
    }))
    .pipe(gulp.dest(config.paths.less.dest));
});

gulp.task('browser-sync', function() {
  if (serverConf !== null) {
    browserSync.init(serverConf);
  } else {
    console.log('BrowserSync config not specified');
  }
});

gulp.task("build", ["less", "images"]);

gulp.task("watch", function() {
  gulp.watch(config.paths.less.path, ["less-dev"]);
});

gulp.task("dev", ["less-dev", "images", "browser-sync", "watch"]);

gulp.task("default", function() {
  console.log('Silence is gold');
});