<?php

namespace App\Factory;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

/**
 * @extends ModelFactory<User>
 *
 * @method        User|Proxy                       create(array|callable $attributes = [])
 * @method static User|Proxy                       createOne(array $attributes = [])
 * @method static User|Proxy                       find(object|array|mixed $criteria)
 * @method static User|Proxy                       findOrCreate(array $attributes)
 * @method static User|Proxy                       first(string $sortedField = 'id')
 * @method static User|Proxy                       last(string $sortedField = 'id')
 * @method static User|Proxy                       random(array $attributes = [])
 * @method static User|Proxy                       randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static User[]|Proxy[]                   all()
 * @method static User[]|Proxy[]                   createMany(int $number, array|callable $attributes = [])
 * @method static User[]|Proxy[]                   createSequence(iterable|callable $sequence)
 * @method static User[]|Proxy[]                   findBy(array $attributes)
 * @method static User[]|Proxy[]                   randomRange(int $min, int $max, array $attributes = [])
 * @method static User[]|Proxy[]                   randomSet(int $number, array $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    private static $em;
    private static $hasher;

    public function __construct(EntityManagerInterface $em)
    {
        $this::$em = $em;
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
        ]);
        
        $this::$hasher = $factory->getPasswordHasher('common');

        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function getDefaults(): array
    {
        return [
            'admin' => 0,
            'email' => self::faker()->email(),
            'name' => self::faker()->name(),
            'password' => $this::$hasher->hash('password'),
            'country' => $this->randomCountry(),
        ];
    }

    protected function randomCountry() {
        $p = $this::$em->createQuery('SELECT c from App\Entity\Country c')->getResult();
        return $p[self::faker()->numberBetween(0, count($p)-1)];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(User $user): void {})
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
