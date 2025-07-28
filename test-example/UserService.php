<?php

namespace App\Service;

/**
 * A simple user service with intentional code quality issues for testing.
 */
class UserService
{
    private $db;
    private $mailer;
    private $logger;

    public function __construct($db, $mailer, $logger)
    {
        $this->db = $db;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    // Missing return type and parameter types
    public function createUser($userData)
    {
        // No input validation
        $user = array();
        $user['name'] = $userData['name'];
        $user['email'] = $userData['email'];
        
        // Direct SQL - security issue
        $sql = "INSERT INTO users (name, email) VALUES ('" . $user['name'] . "', '" . $user['email'] . "')";
        $this->db->query($sql);
        
        // N+1 query problem
        $users = $this->db->query("SELECT * FROM users");
        foreach ($users as $u) {
            $profile = $this->db->query("SELECT * FROM profiles WHERE user_id = " . $u['id']);
            $u['profile'] = $profile;
        }
        
        // Complex nested conditions
        if ($user['email']) {
            if (strpos($user['email'], '@') !== false) {
                if (strlen($user['email']) > 5) {
                    if (filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                        $this->mailer->send($user['email'], 'Welcome');
                    }
                }
            }
        }
        
        return $user;
    }

    // Method doing too many things (SRP violation)
    public function processUserData($id, $updateData, $sendEmail = true)
    {
        // Get user
        $user = $this->db->query("SELECT * FROM users WHERE id = " . $id);
        
        // Update user
        $sql = "UPDATE users SET ";
        foreach ($updateData as $key => $value) {
            $sql .= $key . " = '" . $value . "', ";
        }
        $sql = rtrim($sql, ', ') . " WHERE id = " . $id;
        $this->db->query($sql);
        
        // Send email
        if ($sendEmail) {
            $this->mailer->send($user['email'], 'Profile Updated');
        }
        
        // Log activity
        $this->logger->log('User ' . $id . ' updated');
        
        // Generate report
        $report = array();
        $report['user_id'] = $id;
        $report['changes'] = $updateData;
        $report['timestamp'] = date('Y-m-d H:i:s');
        
        return $report;
    }

    // Unused method with performance issues
    public function calculateUserStats($userId)
    {
        $stats = array();
        
        // Inefficient algorithm
        for ($i = 1; $i <= 1000; $i++) {
            for ($j = 1; $j <= 1000; $j++) {
                $result = $i * $j;
                if ($result == $userId) {
                    $stats['found'] = true;
                }
            }
        }
        
        return $stats;
    }
}
