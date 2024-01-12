<?php

namespace App\Factory;

use App\Entity\Rating;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use App\Factory\UserFactory;
use App\Factory\SeriesFactory;
use DateTime;

/**
 * @extends ModelFactory<Rating>
 *
 * @method        Rating|Proxy                     create(array|callable $attributes = [])
 * @method static Rating|Proxy                     createOne(array $attributes = [])
 * @method static Rating|Proxy                     find(object|array|mixed $criteria)
 * @method static Rating|Proxy                     findOrCreate(array $attributes)
 * @method static Rating|Proxy                     first(string $sortedField = 'id')
 * @method static Rating|Proxy                     last(string $sortedField = 'id')
 * @method static Rating|Proxy                     random(array $attributes = [])
 * @method static Rating|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Rating[]|Proxy[]                 all()
 * @method static Rating[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Rating[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Rating[]|Proxy[]                 findBy(array $attributes)
 * @method static Rating[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Rating[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class RatingFactory extends ModelFactory
{


    private static $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this::$em = $em;
        parent::__construct();
    }


    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'comment' => self::faker()->text(100),
            'date' => DateTime::createFromFormat('m/d/Y h:i:s a', date('m/d/Y h:i:s a')),
            'value' => self::faker()->numberBetween(0,10),
            'user' => UserFactory::random(),
            'series' => SeriesFactory::random()
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Rating $rating): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Rating::class;
    }
}
