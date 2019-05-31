<?php

namespace Paysera\Bundle\RestBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class RoleAndIpStrategy implements SecurityStrategyInterface
{
    private $roleHierarchy;
    private $tokenStorage;
    private $logger;

    private $roles = [];
    private $ips = [];

    public function __construct(
        RoleHierarchyInterface $roleHierarchy,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function setIps(array $ips)
    {
        $this->ips = $ips;
    }

    public function isAllowed(Request $request)
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            $this->logger->debug('Token not found');
            return false;
        }

        $availableRoles = array_map(function (RoleInterface $role) {
            return $role->getRole();
        }, $this->roleHierarchy->getReachableRoles($token->getRoles()));

        $availableRoles = array_unique($availableRoles);

        foreach ($this->roles as $role) {
            if (!in_array($role, $availableRoles, true)) {
                return false;
            }
        }

        if (count($this->ips)) {
            return IpUtils::checkIp($request->getClientIp(), $this->ips);
        }

        return true;
    }
}
