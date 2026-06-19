<?php

namespace Versioon\NovaInlineBadgeField\Http\Controllers;

use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Versioon\NovaInlineBadgeField\InlineBadge;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Panel;

class NovaInlineBadgeFieldController extends Controller
{
    public function update(NovaRequest $request)
    {
        $modelId = $request->_inlineResourceId;
        $attribute = $request->_inlineAttribute;
        $lensUri = $request->_lensUri;

        $resourceClass = $request->resource();
        $resourceValidationRules = $resourceClass::rulesForUpdate($request);
        $fieldValidationRules = $resourceValidationRules[$attribute] ?? null;

        if (!empty($fieldValidationRules)) {
            $request->validate([$attribute => $fieldValidationRules]);
        }

        // Find field in question
        try {
            $model = $request->model()->find($modelId);
            $resource = new $resourceClass($model);
            if ($lensUri) {
                $resource = collect($resource->lenses($request))
                    ->firstWhere(fn (Lens $lens) => $lens->uriKey() === $lensUri);
            }
            $allFields = collect($resource->availableFields($request));
            $field = $this->findField($allFields, $attribute);

            $field->fillInto($request, $model, $attribute);
            $model->save();
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response('', 204);
    }

	/**
	 * Recursively search for the field, including nested Stack fields.
	 */
	protected function findField(Collection $fields, String $attribute): InlineBadge|null
	{
		foreach ($fields as $field) {
			// Direct match with InlineBadge field
			if ($this->isCorrectInlineBadgeField($field, $attribute)) {
				return $field;
			}

			// Search within Stack fields
			if (get_class($field) === Stack::class) {
				foreach ($field->lines as $nestedField) {
					if ($this->isCorrectInlineBadgeField($nestedField, $attribute)) {
						return $nestedField;
					}
				}
			}

            // Search within Panel fields
			if (get_class($field) === Panel::class) {
				foreach ($field->data as $nestedField) {
					if ($this->isCorrectInlineBadgeField($nestedField, $attribute)) {
						return $nestedField;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Check if the field is the InlineBadge field.
	 */
	protected function isCorrectInlineBadgeField($field, $attribute): bool
	{
		return get_class($field) === InlineBadge::class && $field->attribute === $attribute;
	}
}
