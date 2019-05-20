const gulp = require("gulp");
const sass = require("gulp-sass");
const sourcemaps = require("gulp-sourcemaps");
const livereload = require("gulp-livereload");

var sass_config = {
  includePaths: [
    'node_modules/',
  ],
  outputStyle: "compressed"
};

// CSS task
function css() {
  return gulp
    .src('web/themes/custom/union_register/scss/style.scss')
    .pipe(sourcemaps.init())
    .pipe(sass(sass_config)
      .on('error', sass.logError))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('web/themes/custom/union_register/css'));
}

function livereloadStartServer(done) {
  livereload.listen({ 'port': 35777 });
  done();
}

function watchFiles(done) {
  gulp.watch('web/themes/custom/union_register/scss/**/*.scss', css);

  var lr_watcher = gulp.watch([
    'web/libraries/union/source/**/*.css',
    'web/themes/custom/union_register/css/style.css'
  ]);

  lr_watcher.on('change', livereload.changed);

  done();
}

const watch = gulp.parallel(css, watchFiles, livereloadStartServer);

exports.sass = css
exports.default = watch
