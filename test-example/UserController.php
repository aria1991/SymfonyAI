<?php

namespace App\Controller;

use App\Service\UserService;

/**
 * User controller with architecture issues for testing.
 */
class UserController
{
    // Too many dependencies - violation of ISP
    public function __construct(
        private $userService,
        private $emailService,
        private $loggerService,
        private $cacheService,
        private $fileService,
        private $reportService,
        private $notificationService,
        private $auditService
    ) {}

    // Method doing too many things
    public function createUser($request)
    {
        // Input validation
        if (!$request['name']) {
            throw new \Exception('Name required');
        }
        
        // Business logic
        $user = $this->userService->createUser($request);
        
        // Caching
        $this->cacheService->set('user_' . $user['id'], $user);
        
        // Logging
        $this->loggerService->log('User created: ' . $user['id']);
        
        // File operations
        $this->fileService->createUserDirectory($user['id']);
        
        // Reporting
        $this->reportService->generateUserReport($user);
        
        // Notifications
        $this->notificationService->notifyAdmins('New user: ' . $user['name']);
        
        // Audit
        $this->auditService->logUserCreation($user);
        
        return $user;
    }

    // Direct database access in controller
    public function getUserStats($userId)
    {
        $db = new \PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE id = " . $userId);
        return $stmt->fetchAll();
    }

    // No error handling
    public function deleteUser($id)
    {
        $this->userService->delete($id);
        $this->emailService->sendGoodbyeEmail($id);
        return true;
    }
}
