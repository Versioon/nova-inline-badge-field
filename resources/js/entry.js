import IndexField from './components/IndexField';
import DetailField from './components/DetailField';

Nova.booting((Vue, router) => {
  Vue.component('index-inline-badge-field', IndexField);
  Vue.component('detail-inline-badge-field', DetailField);
});
