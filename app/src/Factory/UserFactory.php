<?php

namespace App\Factory;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
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
    
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
        ]);
        
        $hasher = $factory->getPasswordHasher('common');

        return [
            'admin' => 0,
            'email' => self::faker()->email(),
            'name' => self::faker()->name(),
            'password' => $hasher->hash('password'),
        ];
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
