<?php

namespace Versioon\NovaInlineBadgeField;

use Stringable;
use Laravel\Nova\Badge as BadgeComponent;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class InlineBadge extends Select
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'inline-badge-field';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

    /**
     * The labels that should be applied to the field's possible values.
     *
     * @var array<array-key, \Stringable|string>
     */
    public $labels = [];

    /**
     * The callback used to determine the field's label.
     *
     * @var (callable(mixed):(string))|null
     */
    public $labelCallback = null;

    /**
     * The mapping used for matching custom values to in-built badge types.
     *
     * @var array<array-key, string>
     */
    public $map = [];

    /**
     * Indicates if the field should show icons.
     *
     * @var bool
     */
    public $withIcons = false;

    /**
     * The built-in badge types and their corresponding CSS classes.
     *
     * @var array<array-key, string|array<int, string>>
     */
    public $types = [];

    /**
     * The icons that should be applied to the field's possible values.
     *
     * @var array<array-key, string>
     */
    public $icons = [
        'success' => 'check-circle',
        'info' => 'information-circle',
        'danger' => 'exclamation-circle',
        'warning' => 'exclamation-circle',
    ];

    /**
     * Create a new field.
     *
     * @param  \Stringable|string  $name
     * @param  string|callable|object|null  $attribute
     * @param  (callable(mixed, mixed, ?string):(mixed))|null  $resolveCallback
     */
    public function __construct($name, mixed $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->addTypes(BadgeComponent::$types);
    }

    /**
     * Add badge types and their corresponding CSS classes to the built-in ones.
     *
     * @param  array<array-key, string|array<int, string>>  $types
     * @return $this
     */
    public function addTypes(array $types)
    {
        $this->types = array_merge($this->types, $types);

        return $this;
    }

    /**
     * Set the badge types and their corresponding CSS classes.
     *
     * @param  array<array-key, string|array<int, string>>  $types
     * @return $this
     */
    public function types(array $types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Set the labels for each possible field value.
     *
     * @param  array<array-key, string>  $labels
     * @return $this
     */
    public function labels(array $labels)
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Set the callback to be used to determine the field's displayable label.
     *
     * @param  callable(mixed):string  $labelCallback
     * @return $this
     */
    public function label(callable $labelCallback)
    {
        $this->labelCallback = $labelCallback;

        return $this;
    }

    /**
     * Map the possible field values to the built-in badge types.
     *
     * @param  array<array-key, string>  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->map = $map;

        return $this;
    }

    /**
     * Set the field to display icons.
     *
     * @return $this
     */
    public function withIcons()
    {
        $this->withIcons = true;

        return $this;
    }

    /**
     * Set the icons for each possible field value.
     *
     * @param  array<array-key, string>  $icons
     * @return $this
     */
    public function icons(array $icons)
    {
        $this->withIcons = true;
        $this->icons = $icons;

        return $this;
    }

    /**
     * Add the resource key to the field's meta so inline updates can be routed.
     */
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        $this->withMeta(['resourceId' => $resource->getKey()]);

        return parent::resolveAttribute($resource, $attribute);
    }

    /**
     * Resolve the field's value, swapping to a native Select on form views.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        /** @var NovaRequest $novaRequest */
        $novaRequest = app()->make(NovaRequest::class);
        if ($novaRequest->isFormRequest()) {
            $this->component = 'select-field';
        }
    }

    /**
     * Resolve the Badge's CSS classes for the given value.
     */
    protected function badgeClassesFor(mixed $value): string
    {
        $mappedValue = $this->map[$value] ?? $value;
        $classes = $this->types[$mappedValue] ?? '';

        return is_array($classes) ? implode(' ', $classes) : (string) $classes;
    }

    /**
     * Resolve the display icon for the given value.
     */
    protected function iconFor(mixed $value): ?string
    {
        $mappedValue = $this->map[$value] ?? $value;

        return $this->icons[$mappedValue] ?? null;
    }

    /**
     * Resolve the display label for the given value.
     */
    protected function resolveLabelFor(mixed $value): Stringable|string
    {
        if (isset($this->labelCallback)) {
            return call_user_func($this->labelCallback, $value);
        }

        return $this->labels[$value] ?? $this->optionLabelFor($value) ?? $value ?? '';
    }

    /**
     * Find the configured option label for the given value, if any.
     */
    protected function optionLabelFor(mixed $value): Stringable|string|null
    {
        foreach ($this->serializeOptions(false) as $option) {
            if ((string) $option['value'] === (string) $value) {
                return $option['label'];
            }
        }

        return null;
    }

    /**
     * Build a lookup of badge presentation keyed by option value, so the
     * front-end can re-render the badge after an inline change.
     *
     * @return array<string, array{label: \Stringable|string, typeClass: string, icon: string|null}>
     */
    protected function serializeBadges(): array
    {
        return collect($this->serializeOptions(false))
            ->mapWithKeys(fn ($option) => [
                (string) $option['value'] => [
                    'label' => $this->resolveLabelFor($option['value']),
                    'typeClass' => $this->badgeClassesFor($option['value']),
                    'icon' => $this->withIcons ? $this->iconFor($option['value']) : null,
                ],
            ])
            ->all();
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'label' => $this->resolveLabelFor($this->value),
            'typeClass' => $this->badgeClassesFor($this->value),
            'icon' => $this->withIcons ? $this->iconFor($this->value) : null,
            'badges' => $this->serializeBadges(),
        ]);
    }
}
