<?php
declare(strict_types=1);

namespace ClassCRUD\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ClassCRUD\Services\AuthService;
use Twig\Environment;

class AuthController
{
    private AuthService $authService;
    private Environment $twig;

    public function __construct(AuthService $authService, Environment $twig)
    {
        $this->authService = $authService;
        $this->twig = $twig;
    }

    /**
     * Show login form
     */
    public function showLogin(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $html = $this->twig->render('auth/login.twig', [
            'show_sidebar' => false,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Process login
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $user = $this->authService->authenticate($username, $password);
            
            if ($user) {
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['email']; // Use email as username
                $_SESSION['user_name'] = $user['first_name'] . ' ' . ($user['surname'] ?? $user['last_name'] ?? '');
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect to dashboard
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            } else {
                // Invalid credentials
                $html = $this->twig->render('auth/login.twig', [
                    'error' => 'Invalid username or password',
                    'username' => $username,
                    'show_sidebar' => false,
                ]);

                $response->getBody()->write($html);
                return $response->withStatus(401);
            }

        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            
            $html = $this->twig->render('auth/login.twig', [
                'error' => 'An error occurred during login. Please try again.',
                'username' => $username,
                'show_sidebar' => false,
            ]);

            $response->getBody()->write($html);
            return $response->withStatus(500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request, Response $response): Response
    {
        session_start();
        session_destroy();
        
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    /**
     * Show registration form
     */
    public function showRegister(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $html = $this->twig->render('auth/register.twig', [
            'show_sidebar' => false,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Process registration
     */
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate required fields
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match';
        }
        
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        // If there are validation errors, return to form
        if (!empty($errors)) {
            $html = $this->twig->render('auth/register.twig', [
                'errors' => $errors,
                'form_data' => $data,
                'show_sidebar' => false,
            ]);

            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        try {
            // Check if username or email already exists
            if ($this->authService->userExists($data['username'], $data['email'])) {
                $html = $this->twig->render('auth/register.twig', [
                    'error' => 'Username or email already exists',
                    'form_data' => $data,
                    'show_sidebar' => false,
                ]);

                $response->getBody()->write($html);
                return $response->withStatus(400);
            }

            // Create user
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => 'user', // Default role
            ];

            $user = $this->authService->createUser($userData);
            
            if ($user) {
                // Auto-login after registration
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect to dashboard
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            } else {
                throw new \Exception('Failed to create user');
            }

        } catch (\Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            
            $html = $this->twig->render('auth/register.twig', [
                'error' => 'An error occurred during registration. Please try again.',
                'form_data' => $data,
                'show_sidebar' => false,
            ]);

            $response->getBody()->write($html);
            return $response->withStatus(500);
        }
    }
}
