<?php

namespace DigitalCreative\JsonWrapper;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class JsonWrapper extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'json-wrapper';

    /**
     * @var Collection
     */
    public $fields;

    /**
     * Create a new field.
     *
     * @param string|callable|null $attribute
     * @param array $fields
     */
    public function __construct(string $attribute, array $fields = [])
    {
        parent::__construct($attribute, $attribute);

        $this->fields = collect($fields);
    }

    /**
     * Resolve the field's value.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        $this->recursiveResolve($resource, $this->fields, collect($attribute ?? $this->attribute));
    }

    private function recursiveResolve($resource, $fields, Collection $bag)
    {

        foreach ($fields as $field) {

            if ($field instanceof JsonWrapper) {

                $this->recursiveResolve($resource, $field->fields, $bag->merge($field->attribute));

                continue;

            }

            $field->resolve($resource, $bag->merge($field->attribute)->join('->'));

        }

    }

    /**
     * Get the validation rules for this field.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function getRules(NovaRequest $request)
    {

        $rules = parent::getRules($request);

        foreach ($this->fields as $field) {

            $rules = array_merge($rules, $field->getRules($request));

        }

        return $rules;

    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param NovaRequest $request
     * @param object $model
     * @return mixed
     */
    public function fill(NovaRequest $request, $model)
    {

        $clone = $model->newInstance();

        /**
         * @var Field $field
         */
        foreach ($this->fields as $field) {

            $field->fill($request, $clone);

        }

        $request->offsetSet($this->attribute, $clone->toArray());

        return parent::fill($request, $model);

    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge([ 'fields' => $this->fields ], parent::jsonSerialize());
    }
}