<?php

namespace App\Factory;

use App\Entity\Series;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Series>
 *
 * @method        Series|Proxy                     create(array|callable $attributes = [])
 * @method static Series|Proxy                     createOne(array $attributes = [])
 * @method static Series|Proxy                     find(object|array|mixed $criteria)
 * @method static Series|Proxy                     findOrCreate(array $attributes)
 * @method static Series|Proxy                     first(string $sortedField = 'id')
 * @method static Series|Proxy                     last(string $sortedField = 'id')
 * @method static Series|Proxy                     random(array $attributes = [])
 * @method static Series|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Series[]|Proxy[]                 all()
 * @method static Series[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Series[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Series[]|Proxy[]                 findBy(array $attributes)
 * @method static Series[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Series[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class SeriesFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function getDefaults(): array
    {
        return [
            'imdb' => self::faker()->text(128),
            'title' => self::faker()->text(128),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Series $series): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Series::class;
    }
}
