<template>
  <InlineBadgeEditableValue :field="field" :resourceName="resourceName" />
</template>

<script>
import InlineBadgeEditableValue from './InlineBadgeEditableValue';

export default {
  components: { InlineBadgeEditableValue },
  props: ['resourceName', 'field'],
};
</script>
