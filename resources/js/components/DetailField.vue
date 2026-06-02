<template>
  <PanelItem :field="field">
    <template #value>
      <InlineBadgeEditableValue :field="field" :resourceName="resourceName" />
    </template>
  </PanelItem>
</template>

<script>
import InlineBadgeEditableValue from './InlineBadgeEditableValue';

export default {
  components: { InlineBadgeEditableValue },
  props: ['resource', 'resourceName', 'resourceId', 'field'],
};
</script>
