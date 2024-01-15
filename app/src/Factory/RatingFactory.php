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
use App\Entity\Series;
use App\Entity\User;
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
    private static $moy;
    private static $et;



    public function __construct(EntityManagerInterface $em)
    {
        RatingFactory::$em = $em;
        parent::__construct();
    }

    public static function setMoyEt(float $moy, float $et) {
        RatingFactory::$moy = $moy;
        RatingFactory::$et = $et;
    }

    public static function gaussienne($av, $sd): float
    {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
    
        return sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sd + $av;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function getDefaults(): array
    {
        $series = SeriesFactory::random()->object();
        $user = RatingFactory::getCorrectUser($series);
        return [
            'comment' => self::faker()->text(100),
            'date' => DateTime::createFromFormat('m/d/Y h:i:s a', date('m/d/Y h:i:s a')),
            'value' => ((int)round(RatingFactory::gaussienne(RatingFactory::$moy, RatingFactory::$et))%11),
            'user' => $user,
            'series' => $series
        ];
    }

    /**
     * Ensures an user is created with an unique email
     */
    protected function getCorrectUser(Series $series): User
    {
        $repo = $this::$em->getRepository('App\Entity\Rating');
        $user = UserFactory::random()->object();
        $count = 0;

        while ($repo->findOneBy(['series'=>$series, 'user'=>$user]) && $count < 5) {
            $user = UserFactory::random()->object();
            $count++;
        }

        if ($count == 5) {
            $user = UserFactory::createOne()->object();
        }

        return $user;
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
