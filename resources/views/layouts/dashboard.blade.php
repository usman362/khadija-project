<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' Dashboard')</title>

    <script src="https://nobleui.com/html/template/assets/js/color-modes.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://nobleui.com/html/template/assets/vendors/core/core.css">
    <link rel="stylesheet" href="https://nobleui.com/html/template/assets/css/demo1/style.css">
    <link rel="shortcut icon" href="https://nobleui.com/html/template/assets/images/favicon.png" />
</head>

<body>
    <div class="main-wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">{{ config('app.name', 'App') }}</a>
                <div class="sidebar-toggler">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <div class="sidebar-body">
                <ul class="nav" id="sidebarNav">
                    <li class="nav-item nav-category">Main</li>
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}"
                            class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="link-icon" data-lucide="layout-dashboard"></i>
                            <span class="link-title">Dashboard</span>
                        </a>
                    </li>

                    @can('events.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.events.index') }}"
                                class="nav-link {{ request()->routeIs('app.events.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="calendar-days"></i>
                                <span class="link-title">Events</span>
                            </a>
                        </li>
                    @endcan

                    @can('bookings.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.bookings.index') }}"
                                class="nav-link {{ request()->routeIs('app.bookings.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="book-check"></i>
                                <span class="link-title">Bookings</span>
                            </a>
                        </li>
                    @endcan

                    @can('messages.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.chat.index') }}"
                                class="nav-link {{ request()->routeIs('app.chat.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="message-circle"></i>
                                <span class="link-title">Chat</span>
                            </a>
                        </li>
                    @endcan

                    @can('membership_plans.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.membership-plans.index') }}"
                                class="nav-link {{ request()->routeIs('app.membership-plans.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="crown"></i>
                                <span class="link-title">Membership Plans</span>
                            </a>
                        </li>
                    @endcan

                    @can('membership_plans.create')
                        <li class="nav-item">
                            <a href="{{ route('app.admin.membership-plans.index') }}"
                                class="nav-link {{ request()->routeIs('app.admin.membership-plans.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="settings-2"></i>
                                <span class="link-title">Manage Plans</span>
                            </a>
                        </li>
                    @endcan

                    @can('agreements.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.agreements.index') }}"
                                class="nav-link {{ request()->routeIs('app.agreements.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="file-signature"></i>
                                <span class="link-title">AI Agreements</span>
                            </a>
                        </li>
                    @endcan

                    @can('agreement_log.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.agreement-log.index') }}"
                                class="nav-link {{ request()->routeIs('app.agreement-log.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="scroll-text"></i>
                                <span class="link-title">Agreement Log</span>
                            </a>
                        </li>
                    @endcan

                    @can('users.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.users.index') }}"
                                class="nav-link {{ request()->routeIs('app.users.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="shield-check"></i>
                                <span class="link-title">Users</span>
                            </a>
                        </li>
                    @endcan

                    @can('roles.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.roles.index') }}"
                                class="nav-link {{ request()->routeIs('app.roles.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="shield"></i>
                                <span class="link-title">Roles</span>
                            </a>
                        </li>
                    @endcan

                    @can('permissions.view_any')
                        <li class="nav-item">
                            <a href="{{ route('app.permissions.index') }}"
                                class="nav-link {{ request()->routeIs('app.permissions.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="key-round"></i>
                                <span class="link-title">Permissions</span>
                            </a>
                        </li>
                    @endcan

                    @can('payments.view')
                        <li class="nav-item">
                            <a href="{{ route('app.payments.history') }}"
                                class="nav-link {{ request()->routeIs('app.payments.*') ? 'active' : '' }}">
                                <i class="link-icon" data-lucide="credit-card"></i>
                                <span class="link-title">Payment History</span>
                            </a>
                        </li>
                    @endcan

                    @can('payment_settings.manage')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('app.admin.settings.*') ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse" href="#settingsSubmenu" role="button"
                                aria-expanded="{{ request()->routeIs('app.admin.settings.*') ? 'true' : 'false' }}"
                                aria-controls="settingsSubmenu">
                                <i class="link-icon" data-lucide="settings"></i>
                                <span class="link-title">Settings</span>
                                <i class="link-arrow" data-lucide="chevron-down"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('app.admin.settings.*') ? 'show' : '' }}" id="settingsSubmenu">
                                <ul class="nav sub-menu">
                                    <li class="nav-item">
                                        <a href="{{ route('app.admin.settings.payments') }}"
                                            class="nav-link {{ request()->routeIs('app.admin.settings.payments*') ? 'active' : '' }}">
                                            Payment Settings
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endcan
                </ul>
            </div>
        </nav>

        <div class="page-wrapper">
            <nav class="navbar">
                <a href="#" class="sidebar-toggler"><i data-lucide="menu"></i></a>
                <div class="navbar-content ms-auto">
                    <ul class="navbar-nav">
                        <li class="theme-switcher-wrapper nav-item">
                            <input type="checkbox" value="" id="theme-switcher" />
                            <label for="theme-switcher">
                                <div class="box">
                                    <div class="ball"></div>
                                    <div class="icons">
                                        <i data-lucide="sun"></i>
                                        <i data-lucide="moon"></i>
                                    </div>
                                </div>
                            </label>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i data-lucide="mail"></i>
                            </a>
                            <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
                                <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                                    <p>9 New Messages</p>
                                    <a href="javascript:;" class="text-secondary">Clear all</a>
                                </div>
                                <div class="p-1">
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div class="me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face2.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="d-flex justify-content-between flex-grow-1">
                                            <div class="me-4">
                                                <p>Leonardo Payne</p>
                                                <p class="fs-12px text-secondary">
                                                    Project status
                                                </p>
                                            </div>
                                            <p class="fs-12px text-secondary">
                                                2 min ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div class="me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face3.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="d-flex justify-content-between flex-grow-1">
                                            <div class="me-4">
                                                <p>Carl Henson</p>
                                                <p class="fs-12px text-secondary">
                                                    Client meeting
                                                </p>
                                            </div>
                                            <p class="fs-12px text-secondary">
                                                30 min ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div class="me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face4.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="d-flex justify-content-between flex-grow-1">
                                            <div class="me-4">
                                                <p>Jensen Combs</p>
                                                <p class="fs-12px text-secondary">
                                                    Project updates
                                                </p>
                                            </div>
                                            <p class="fs-12px text-secondary">
                                                1 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div class="me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face5.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="d-flex justify-content-between flex-grow-1">
                                            <div class="me-4">
                                                <p>Amiah Burton</p>
                                                <p class="fs-12px text-secondary">
                                                    Project deatline
                                                </p>
                                            </div>
                                            <p class="fs-12px text-secondary">
                                                2 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div class="me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face6.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="d-flex justify-content-between flex-grow-1">
                                            <div class="me-4">
                                                <p>Yaretzi Mayo</p>
                                                <p class="fs-12px text-secondary">
                                                    New record
                                                </p>
                                            </div>
                                            <p class="fs-12px text-secondary">
                                                5 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                </div>
                                <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                                    <a href="javascript:;">View all</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown"
                                role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i data-lucide="bell"></i>
                                <div class="indicator">
                                    <div class="circle"></div>
                                </div>
                            </a>
                            <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown">
                                <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                                    <p>6 New Notifications</p>
                                    <a href="javascript:;" class="text-secondary">Clear all</a>
                                </div>
                                <div class="p-1">
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div
                                            class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                                            <i class="icon-sm text-white" data-lucide="gift"></i>
                                        </div>
                                        <div class="flex-grow-1 me-2">
                                            <p>New Order Recieved</p>
                                            <p class="fs-12px text-secondary">
                                                30 min ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div
                                            class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                                            <i class="icon-sm text-white" data-lucide="alert-circle"></i>
                                        </div>
                                        <div class="flex-grow-1 me-2">
                                            <p>Server Limit Reached!</p>
                                            <p class="fs-12px text-secondary">
                                                1 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div
                                            class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                                            <img class="w-30px h-30px rounded-circle"
                                                src="https://nobleui.com/html/template/assets/images/faces/face6.jpg"
                                                alt="userr" />
                                        </div>
                                        <div class="flex-grow-1 me-2">
                                            <p>New customer registered</p>
                                            <p class="fs-12px text-secondary">
                                                2 sec ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div
                                            class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                                            <i class="icon-sm text-white" data-lucide="layers"></i>
                                        </div>
                                        <div class="flex-grow-1 me-2">
                                            <p>Apps are ready for update</p>
                                            <p class="fs-12px text-secondary">
                                                5 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                    <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                                        <div
                                            class="w-30px h-30px d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                                            <i class="icon-sm text-white" data-lucide="download"></i>
                                        </div>
                                        <div class="flex-grow-1 me-2">
                                            <p>Download completed</p>
                                            <p class="fs-12px text-secondary">
                                                6 hrs ago
                                            </p>
                                        </div>
                                    </a>
                                </div>
                                <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                                    <a href="javascript:;">View all</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img class="w-30px h-30px ms-1 rounded-circle"
                                    src="https://nobleui.com/html/template/assets/images/faces/face1.jpg"
                                    alt="profile" />
                            </a>
                            <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
                                <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
                                    <div class="mb-3">
                                        <img class="w-80px h-80px rounded-circle"
                                            src="https://nobleui.com/html/template/assets/images/faces/face1.jpg"
                                            alt="" />
                                    </div>
                                    <div class="text-center">
                                        <p class="fs-16px fw-bolder">
                                            {{ auth()->user()?->name }}
                                        </p>
                                        <p class="fs-12px text-secondary">
                                            {{ auth()->user()?->email }}
                                        </p>
                                    </div>
                                </div>
                                <ul class="list-unstyled p-1">
                                    <li>
                                        <a href="javascript:void(0)"
                                            class="dropdown-item py-2 text-body ms-0">
                                            <i class="me-2 icon-md" data-lucide="user"></i>
                                            <span>Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0)" class="dropdown-item py-2 text-body ms-0">
                                            <i class="me-2 icon-md" data-lucide="edit"></i>
                                            <span>Edit Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('logout') }}" class="dropdown-item py-2 text-body ms-0"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="me-2 icon-md" data-lucide="log-out"></i>
                                            <span>Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </nav>

            <div class="page-content">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://nobleui.com/html/template/assets/vendors/core/core.js"></script>
    <script src="{{asset('assets/js/app.js')}}"></script>
    @stack('scripts')
</body>

</html>
