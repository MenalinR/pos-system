<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Installation Wizard</title>
    <style>
        :root {
            --primary: #ff2d20;
            --primary-dark: #e6261a;
            --secondary: #1d68a7;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 4px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .installation-wrapper {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .installation-header {
            background: var(--primary);
            color: white;
            padding: 1.5rem 2rem;
            text-align: center;
        }
        
        .installation-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .installation-header p {
            opacity: 0.9;
        }
        
        .installation-body {
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gray-light);
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .step.completed .step-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 45, 32, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input {
            margin-right: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--gray);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn:disabled {
            background: var(--gray-light);
            color: var(--gray);
            cursor: not-allowed;
        }
        
        .installation-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .requirements-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }
        
        .requirements-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: center;
        }
        
        .requirements-list li:last-child {
            border-bottom: none;
        }
        
        .requirement-status {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 0.75rem;
            text-align: center;
            line-height: 20px;
            font-size: 0.75rem;
        }
        
        .status-success {
            background: var(--success);
            color: white;
        }
        
        .status-error {
            background: var(--danger);
            color: white;
        }
        
        .status-warning {
            background: var(--warning);
            color: var(--dark);
        }
        
        .progress-container {
            margin: 1.5rem 0;
        }
        
        .progress-bar {
            height: 10px;
            background: var(--gray-light);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.5s ease;
        }
        
        .success-screen {
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
        }
        
        .hidden {
            display: none;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .debug-info h4 {
            margin-bottom: 0.5rem;
            color: var(--gray);
        }
    </style>
</head>
<body>
    <div class="installation-wrapper">
        <div class="installation-header">
            <h1>Laravel Installation</h1>
            <p>Welcome to the Laravel installation wizard. This will set up your new Laravel application.</p>
        </div>
        
        <div class="installation-body">
            @yield('content')
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @yield('scripts')
</body>
</html>