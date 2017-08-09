<?php

namespace AppBundle\Configuration;

use JavierEguiluz\Bundle\EasyAdminBundle\Cache\CacheManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EasyAdminConfigManager extends ConfigManager
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, CacheManager $cacheManager, PropertyAccessorInterface $propertyAccessor, array $originalBackendConfig = [], $debug = false)
    {
        parent::__construct($cacheManager, $propertyAccessor, $originalBackendConfig, $debug);
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    private $cache = [];

    public function getBackendConfig($propertyPath = null)
    {
        $cacheKey = $propertyPath ?: '';
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $config = parent::getBackendConfig($propertyPath);

        $token = $this->tokenStorage->getToken();
        if ($token) {
            if (is_array($config)) {
                // Filter config by roles.
                $config = self::arrayFilterRecursive($config, function ($item) {
                    // If key "roles" is not set or the value is an associative array, we want to keep the value.
                    if (!isset($item['roles']) || self::isAssoc($item['roles'])) {
                        return true;
                    }

                    $roles = $item['roles'];
                    if (!is_array($roles)) {
                        $roles = [$roles];
                    }

                    return $this->hasRole($roles);
                });

                if ($propertyPath === 'design.menu') {
                    $this->reindexMenu($config);
                }
            }
        }

        $this->cache[$cacheKey] = $config;

        return $config;
    }

    private function reindexMenu(array &$config, $menuIndex = null)
    {
        $config = array_values($config);
        foreach ($config as $index => &$item) {
            if ($menuIndex === null) {
                $item['menu_index'] = $index;
                if (isset($item['children'])) {
                    $this->reindexMenu($item['children'], $index);
                }
            } else {
                $item['menu_index'] = $menuIndex;
                $item['submenu_index'] = $index;
            }
        }
    }

    private function hasRole(array $roleNames)
    {
        foreach ($roleNames as $roleName) {
            if ($this->authorizationChecker->isGranted($roleName)) {
                return true;
            }
        }

        return false;
    }

    // @see http://php.net/manual/en/function.array-filter.php#87581
    private static function arrayFilterRecursive(array $input, callable $callback)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterRecursive($value, $callback);
            }
        }

        return array_filter($input, $callback);
    }

    // @see http://stackoverflow.com/a/173479
    private static function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
