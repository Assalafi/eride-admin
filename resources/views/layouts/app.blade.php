<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Links Of CSS File -->
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/prism.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/rangeslider.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/google-icon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ app_favicon() }}">

    <title>@yield('title', app_name())</title>

    <!-- Global Pagination Styles -->
    <style>
        .pagination {
            --bs-pagination-padding-x: 0.4rem;
            --bs-pagination-padding-y: 0.2rem;
            --bs-pagination-font-size: 0.75rem;
            margin-bottom: 0;
        }

        .pagination .page-item {
            margin-right: 3px !important;
        }

        .pagination .page-item .page-link {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
            min-width: auto !important;
            height: auto !important;
        }

        .pagination .page-item .page-link.icon,
        .pagination .page-item .page-link.icon i {
            width: auto !important;
            height: auto !important;
            line-height: 1.2 !important;
            padding: 0.25rem 0.4rem !important;
        }

        nav.d-flex p.small {
            font-size: 0.75rem !important;
            margin-bottom: 0;
        }
    </style>

    @stack('styles')
</head>

<body class="boxed-size">
    @auth
        <!-- Start Sidebar Area -->
        <div class="sidebar-area" id="sidebar-area">
            <div class="logo position-relative">
                <a href="{{ route('dashboard') }}"
                    class="d-block text-decoration-none position-relative d-flex align-items-center">
                    @if (app_logo())
                        <img src="{{ app_logo() }}" alt="{{ app_name() }}"
                            style="max-height: 40px; max-width: 150px;" class="me-2">
                    @else
                        <span class="material-symbols-outlined menu-icon text-primary"
                            style="font-size: 32px;">directions_car</span>
                        <span class="logo-text fw-bold text-dark">{{ app_name() }}</span>
                    @endif
                </a>
                <button
                    class="sidebar-burger-menu bg-transparent p-0 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y"
                    id="sidebar-burger-menu">
                    <i data-feather="x"></i>
                </button>
            </div>

            <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
                <ul class="menu-inner">
                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">MAIN</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <span class="material-symbols-outlined menu-icon">dashboard</span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.activities.index') }}" class="menu-link">
                            <span class="material-symbols-outlined menu-icon">notifications_active</span>
                            <span class="title">Activities & Requests</span>
                        </a>
                    </li>

                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">OPERATIONS</span>
                    </li>

                    @can('view drivers')
                        <li class="menu-item {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.drivers.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">group</span>
                                <span class="title">Drivers</span>
                            </a>
                        </li>
                    @endcan

                    @can('view vehicles')
                        <li class="menu-item {{ request()->routeIs('admin.vehicles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.vehicles.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">directions_car</span>
                                <span class="title">Vehicles</span>
                            </a>
                        </li>
                    @endcan

                    @can('assign vehicles')
                        <li class="menu-item {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.assignments.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">swap_horiz</span>
                                <span class="title">Assignments</span>
                            </a>
                        </li>
                    @endcan

                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">FINANCIAL</span>
                    </li>

                    @can('view payments')
                        <li class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.payments.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">payments</span>
                                <span class="title">Payments</span>
                            </a>
                        </li>
                    @endcan

                    @can('view hire purchase')
                        <li class="menu-item {{ request()->routeIs('admin.hire-purchase.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.hire-purchase.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">directions_car</span>
                                <span class="title">Hire Purchase</span>
                            </a>
                        </li>
                    @endcan

                    @can('approve payments')
                        <li class="menu-item {{ request()->routeIs('admin.wallet-funding.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.wallet-funding.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                                <span class="title">Wallet Funding</span>
                            </a>
                        </li>
                    @endcan

                    @role('Driver')
                        <li class="menu-item {{ request()->routeIs('driver.wallet-funding.*') ? 'active' : '' }}">
                            <a href="{{ route('driver.wallet-funding.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                                <span class="title">Wallet Funding</span>
                            </a>
                        </li>
                    @endrole

                    @can('view company account')
                        <li class="menu-item {{ request()->routeIs('admin.company-account.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.company-account.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">account_balance</span>
                                <span class="title">Company Account</span>
                            </a>
                        </li>
                    @endcan

                    @can('view company account')
                        <li class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <a href="{{ route('accounts.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                                <span class="title">Account Management</span>
                            </a>
                        </li>
                    @endcan

                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">MAINTENANCE</span>
                    </li>

                    @can('view maintenance requests')
                        <li class="menu-item {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.maintenance.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">build</span>
                                <span class="title">Maintenance Requests</span>
                            </a>
                        </li>
                    @endcan

                    @can('view inventory')
                        <li class="menu-item {{ request()->routeIs('admin.parts.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.parts.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">inventory_2</span>
                                <span class="title">Parts & Inventory</span>
                            </a>
                        </li>
                    @endcan

                    @can('view charging requests')
                        <li class="menu-item {{ request()->routeIs('admin.charging.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.charging.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">ev_station</span>
                                <span class="title">Charging Requests</span>
                            </a>
                        </li>
                    @endcan

                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">SYSTEM</span>
                    </li>

                    @can('view branches')
                        <li class="menu-item {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.branches.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">business</span>
                                <span class="title">Branches</span>
                            </a>
                        </li>
                    @endcan

                    @role('Super Admin')
                        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">manage_accounts</span>
                                <span class="title">User Management</span>
                            </a>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">admin_panel_settings</span>
                                <span class="title">Role Management</span>
                            </a>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.index') }}" class="menu-link">
                                <span class="material-symbols-outlined menu-icon">settings</span>
                                <span class="title">Settings</span>
                            </a>
                        </li>
                    @endrole

                    <li class="menu-title small text-uppercase">
                        <span class="menu-title-text">ACCOUNT</span>
                    </li>

                    <li class="menu-item">
                        <a href="javascript:void(0);"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="menu-link">
                            <span class="material-symbols-outlined menu-icon">logout</span>
                            <span class="title">Logout</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </aside>
        </div>
        <!-- End Sidebar Area -->

        <!-- Start Main Content Area -->
        <div class="container-fluid">
            <div class="main-content d-flex flex-column">
                <!-- Start Header Area -->
                <header class="header-area bg-white mb-4 rounded-bottom-15" id="header-area">
                    <div class="row align-items-center">
                        <div class="col-lg-4 col-sm-6">
                            <div class="left-header-content">
                                <ul
                                    class="d-flex align-items-center ps-0 mb-0 list-unstyled justify-content-center justify-content-sm-start">
                                    <li>
                                        <button class="header-burger-menu bg-transparent p-0 border-0"
                                            id="header-burger-menu">
                                            <span class="material-symbols-outlined">menu</span>
                                        </button>
                                    </li>
                                    <li>
                                        <form class="src-form position-relative">
                                            <input type="text" class="form-control" placeholder="Search here...">
                                            <button type="submit"
                                                class="src-btn position-absolute top-50 end-0 translate-middle-y bg-transparent p-0 border-0">
                                                <span class="material-symbols-outlined">search</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-8 col-sm-6">
                            <div class="right-header-content mt-2 mt-sm-0">
                                <ul
                                    class="d-flex align-items-center justify-content-center justify-content-sm-end ps-0 mb-0 list-unstyled">
                                    <li class="header-right-item">
                                        <div class="dropdown notifications noti">
                                            <button class="btn btn-secondary border-0 p-0 position-relative badge"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="material-symbols-outlined">notifications</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-lg p-0 border-0 p-0 dropdown-menu-end">
                                                <div class="d-flex justify-content-between align-items-center title">
                                                    <span class="fw-semibold fs-15 text-secondary">Notifications</span>
                                                </div>
                                                <div class="max-h-217" data-simplebar>
                                                    <div class="notification-menu">
                                                        <p class="text-center text-muted py-3">No new notifications</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="header-right-item">
                                        <div class="dropdown admin-profile">
                                            <div class="d-xxl-flex align-items-center bg-transparent border-0 text-start p-0 cursor"
                                                data-bs-toggle="dropdown">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="rounded-circle wh-54 bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-5">
                                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-none d-xxl-block">
                                                            <span
                                                                class="text-secondary fw-medium d-block mb-2">{{ auth()->user()->name }}</span>
                                                            <span
                                                                class="fs-12">{{ auth()->user()->roles->first()->name ?? 'User' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <ul class="dropdown-menu dropdown-lg border-0 p-4 dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item admin-item-link d-flex align-items-center text-body"
                                                        href="javascript:void(0);">
                                                        <i class="material-symbols-outlined">account_circle</i>
                                                        <span class="ms-2">My Profile</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item admin-item-link d-flex align-items-center text-body"
                                                        href="javascript:void(0);"
                                                        onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
                                                        <i class="material-symbols-outlined">logout</i>
                                                        <span class="ms-2">Logout</span>
                                                    </a>
                                                    <form id="logout-form-header" action="{{ route('logout') }}"
                                                        method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>
                <!-- End Header Area -->

                <div class="main-content-container overflow-hidden">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <div class="flex-grow-1"></div>

                <!-- Start Footer Area -->
                <footer class="footer-area bg-white text-center rounded-top-7">
                    <p class="fs-14">© <span class="text-primary-div">eRide</span> Transport Management System
                        {{ date('Y') }}</p>
                </footer>
                <!-- End Footer Area -->
            </div>
        </div>
        <!-- End Main Content Area -->
    @endauth

    @guest
        @yield('content')
    @endguest

    <!-- Link Of JS File -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/sidebar-menu.js') }}"></script>
    <script src="{{ asset('assets/js/dragdrop.js') }}"></script>
    <script src="{{ asset('assets/js/rangeslider.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>

    <script>
        // Initialize Feather Icons
        feather.replace();
    </script>

    @stack('scripts')
</body>

</html>
