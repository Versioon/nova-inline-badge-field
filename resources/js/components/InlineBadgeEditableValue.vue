<template>
  <div :class="`nova-inline-badge-field text-${field.textAlign}${loading ? ' -loading' : ''}`">
    <Badge :extra-classes="currentBadge.typeClass">
      <template #icon>
        <span v-if="currentBadge.icon" class="mr-1 -ml-1">
          <Icon :name="currentBadge.icon" type="solid" class="inline-block" />
        </span>
      </template>

      <span class="nova-inline-badge-field-label">
        <template v-if="hasValue">{{ currentBadge.label }}</template>
        <template v-else>&mdash;</template>

        <svg
          v-if="editable"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          height="12"
          width="12"
          style="margin-left: 4px"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
      </span>
    </Badge>

    <select
      v-if="editable"
      class="nova-inline-badge-field-select"
      :value="currentValueString"
      :disabled="loading"
      @change="onChange"
      @click.stop
      @mousedown.stop
    >
      <option v-if="field.nullable" value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>

<script>
import { Icon } from 'laravel-nova-ui';
import InteractsWithResourceInformation from 'nova/mixins/InteractsWithResourceInformation';

export default {
  props: ['resourceName', 'field'],
  mixins: [InteractsWithResourceInformation],
  components: { Icon },

  data: () => ({
    loading: false,
    currentValue: null,
  }),

  created() {
    this.currentValue = this.field.value;
  },

  methods: {
    async onChange(e) {
      const newValue = e.target.value;
      if (newValue === this.currentValueString) return;
      await this.updateFieldValue(newValue);
    },

    async updateFieldValue(newValue) {
      this.loading = true;
      try {
        const matchedLensPath = window.location.pathname.match(`/resources/${this.resourceName}/lens/([^/]+)`);
        await Nova.request().post(`/nova-vendor/nova-inline-badge-field/update/${this.resourceName}`, {
          _lensUri: matchedLensPath ? matchedLensPath[1] : null,
          _inlineResourceId: this.field.resourceId,
          _inlineAttribute: this.field.attribute,
          [this.field.attribute]: newValue,
          extraData: this.field.extraData,
        });

        this.currentValue = newValue;
        this.field.value = newValue;

        Nova.success(
          this.__('The :resource was updated!', {
            resource: this.resourceInformation.singularLabel.toLowerCase(),
          })
        );
      } catch (e) {
        console.error(e);
        Nova.error(this.__('There was a problem submitting the form.'));
      }
      this.loading = false;
    },
  },

  computed: {
    options() {
      return this.field.options || [];
    },

    editable() {
      return !this.field.readonly && this.options.length > 0;
    },

    hasValue() {
      return this.currentValue !== null && this.currentValue !== '';
    },

    currentValueString() {
      return this.currentValue == null ? '' : String(this.currentValue);
    },

    currentBadge() {
      const badges = this.field.badges || {};
      return (
        badges[this.currentValueString] || {
          label: this.field.label,
          typeClass: this.field.typeClass,
          icon: this.field.icon,
        }
      );
    },

    placeholder() {
      return this.field.placeholder || '—';
    },
  },
};
</script>

<style lang="scss">
.nova-inline-badge-field {
  position: relative;
  display: inline-flex;
  align-items: center;
  max-width: 100%;

  &.-loading {
    opacity: 0.5;
  }

  .nova-inline-badge-field-label {
    display: inline-flex;
    align-items: center;
  }

  .nova-inline-badge-field-caret {
    height: 0.85em;
    width: 0.85em;
    margin-left: 2px;
    margin-right: -2px;
    opacity: 0.7;
  }

  // Transparent native <select> overlaid on top of the badge so a click
  // anywhere on the badge opens the OS-native dropdown.
  > select.nova-inline-badge-field-select {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    border: 0;
    opacity: 0;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
  }

  > select.nova-inline-badge-field-select:disabled {
    cursor: default;
  }
}
</style>
