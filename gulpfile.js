const gulp = require("gulp");
const sass = require("gulp-sass");
const sourcemaps = require("gulp-sourcemaps");
const livereload = require("gulp-livereload");

// CSS task
function css() {
  return gulp
    .src('web/themes/custom/union_register/scss/style.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: "compressed" })
      .on('error', sass.logError))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('web/themes/custom/union_register/css'))
    .pipe(livereload());
}

function livereloadInit(done) {
  livereload.listen({
    'port': 35777
  });
  done();
}

function unionLivereload() {
  return gulp
    .src('web/libraries/union/source/components/**/*.css')
    .pipe(livereload());
}

function watchFiles(done) {
  console.log('Watching for .scss file changes in /scss.');
  gulp.watch('web/themes/custom/union_register/scss/**/*.scss', css);
  gulp.watch('web/libraries/union/source/components/**/*.css', unionLivereload);
  done();
}

const watch = gulp.parallel(watchFiles, livereloadInit);

exports.sass = css
exports.default = watch
