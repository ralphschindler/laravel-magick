let mix = require('laravel-mix')

mix.js('resources/js/nova.js', 'dist')
  .vue()
  .options({ terser: { extractComments: false } })
  .sourceMaps()

// mix.setPublicPath('dist')
//     .js('resources/js/nova.js', 'js')
//
//     .sourceMaps()
