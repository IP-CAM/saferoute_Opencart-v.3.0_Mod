const { join } = require('path');

const $ = {
  gulp:   require('gulp'),
  zip:    require('gulp-zip'),
  clean:  require('gulp-clean'),
  watch:  require('gulp-watch'),
  rename: require('gulp-rename'),
};


const buildDir  = './build';
const srcDir = './src';

const moduleFiles = join(srcDir, '**/*.*');

const moduleFileName  = 'saferoute.ocmod.zip';


// Удаление старых файлов сборки
$.gulp.task('_clean', () =>
  $.gulp.src(join(buildDir, '*.*'), { read: false })
    .pipe($.clean())
);

// Сборка модуля в установочный архив
$.gulp.task('_build', () =>
  $.gulp.src(moduleFiles, { base: srcDir })
    .pipe($.zip(moduleFileName))
    .pipe($.gulp.dest(buildDir))
);

// Мониторинг изменений и пересборка
$.gulp.task('_watch', () =>
  $.watch([moduleFiles], $.gulp.series('_clean', '_build'))
);


$.gulp.task('default', $.gulp.series('_clean', '_build', '_watch'));