<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class ArchiveScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ["Archive","WithArchived","UnArchive","WithOutArchived","OnlyArchived"];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereNotIn($model->getQualifiedArchivedByColumn(), [auth()->id()]);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Get the "archived by" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getArchivedByColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedArchivedByColumn();
        }

        return $builder->getModel()->getArchivedByColumn();
    }

    /**
     * Add the archive extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addArchive(Builder $builder)
    {
        $builder->macro('archive', function(Builder $builder) {
            $archivedBy = $builder->getModel()->{$builder->getModel()->getArchivedByColumn()};
            if( ! is_array($archivedBy) || ! in_array(auth()->id(),$archivedBy)){
                $builder->getModel()->push($builder->getModel()->getArchivedByColumn(),auth()->id());
            }
        });
    }

    /**
     * Add the with archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return Builder
     */
    protected function addWithArchived(Builder $builder)
    {
        $builder->macro('withArchived', function(Builder $builder, $withArchived = true){
            if(! $withArchived){
                return $builder->withoutArchived();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the un archive extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return Builder
     */
    protected function addUnArchive(Builder $builder)
    {
        $builder->macro('unArchive', function(Builder $builder){
            return $builder->withoutGlobalScope($this)->pull($builder->getModel()->getArchivedByColumn(),auth()->id()); 
        });
    }

    /**
     * Add the without archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return Builder
     */
    protected function addWithoutArchived(Builder $builder)
    {
        $builder->macro('withoutArchived', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNotIn($model->getQualifiedArchivedByColumn(),[auth()->id()]);

            return $builder;
        });
    }

    /**
     * Add the only archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return Builder
     */
    protected function addOnlyArchived(Builder $builder)
    {
        $builder->macro('onlyArchived', function (Builder $builder){
            $model = $builder->getModel();
            return $builder->withoutGlobalScope($this)->whereIn($model->getQualifiedArchivedByColumn(),[auth()->id()]);
        });
    }
}