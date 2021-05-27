import IndexField from './nova/IndexField.vue'
import DetailField from './nova/DetailField.vue'
import FormField from './nova/FormField.vue'

Nova.booting((Vue, router, store) => {
  Vue.component('index-magick', IndexField)
  Vue.component('detail-magick', DetailField)
  Vue.component('form-magick', FormField)
})
