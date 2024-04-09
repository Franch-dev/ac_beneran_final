@extends('layouts.app')

@section('title', 'Sitemap - AC Beneran')

@section('content')
<div class="sitemap-container">
    <header class="sitemap-header">
        <div class="container">
            <h1><i class="fas fa-sitemap"></i> Sitemap</h1>
            <p class="lead">Complete route mapping for AC Beneran application</p>
            <div class="header-actions">
                <button onclick="window.print()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('sitemap.json') }}" class="btn btn-outline" download>
                    <i class="fas fa-download"></i> JSON
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="sitemap-nav">
            <ul>
                <li><a href="#public">Public</a></li>
                <li><a href="#authenticated">Authenticated</a></li>
                <li><a href="#frontdesk">Frontdesk/Admin</a></li>
                <li><a href="#manager">Manager/Admin</a></li>
                <li><a href="#admin">Admin</a></li>
                <li><a href="#technician">Technician</a></li>
                <li><a href="#viewer">Viewer</a></li>
                <li><a href="#modules">Modules</a></li>
            </ul>
        </nav>

        @php
        $sitemap = config('sitemap');
        @endphp

        <!-- Public Routes -->
        <section id="public" class="sitemap-section">
            <h2><i class="fas fa-globe"></i> Public Routes</h2>
            <p class="section-desc">{{ $sitemap['public']['description'] }}</p>
            <div class="route-cards">
                @foreach($sitemap['public']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Authenticated Routes -->
        <section id="authenticated" class="sitemap-section">
            <h2><i class="fas fa-lock"></i> Authenticated Routes</h2>
            <p class="section-desc">{{ $sitemap['authenticated']['description'] }}</p>
            <div class="route-cards">
                @foreach($sitemap['authenticated']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Frontdesk/Admin Routes -->
        <section id="frontdesk" class="sitemap-section">
            <h2><i class="fas fa-user-tie"></i> Frontdesk & Admin</h2>
            <p class="section-desc">{{ $sitemap['frontdesk_admin']['description'] }} (Roles: {{ implode(', ', $sitemap['frontdesk_admin']['roles']) }})</p>
            <div class="route-cards">
                @foreach($sitemap['frontdesk_admin']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Manager/Admin Routes -->
        <section id="manager" class="sitemap-section">
            <h2><i class="fas fa-clipboard-check"></i> Manager & Admin</h2>
            <p class="section-desc">{{ $sitemap['manager_admin']['description'] }} (Roles: {{ implode(', ', $sitemap['manager_admin']['roles']) }})</p>
            <div class="route-cards">
                @foreach($sitemap['manager_admin']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Admin Routes -->
        <section id="admin" class="sitemap-section">
            <h2><i class="fas fa-crown"></i> Admin Only</h2>
            <p class="section-desc">{{ $sitemap['admin']['description'] }} (Roles: {{ implode(', ', $sitemap['admin']['roles']) }})</p>
            <div class="route-cards">
                @foreach($sitemap['admin']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Technician Routes -->
        <section id="technician" class="sitemap-section">
            <h2><i class="fas fa-tools"></i> Technician</h2>
            <p class="section-desc">{{ $sitemap['technician']['description'] }} (Roles: {{ implode(', ', $sitemap['technician']['roles']) }})</p>
            <div class="route-cards">
                @foreach($sitemap['technician']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Viewer Routes -->
        <section id="viewer" class="sitemap-section">
            <h2><i class="fas fa-eye"></i> Viewer/Auditor</h2>
            <p class="section-desc">{{ $sitemap['viewer']['description'] }} (Roles: {{ implode(', ', $sitemap['viewer']['roles']) }})</p>
            <div class="route-cards">
                @foreach($sitemap['viewer']['routes'] as $route)
                <div class="route-card">
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Module Routes -->
        <section id="modules" class="sitemap-section">
            <h2><i class="fas fa-th-large"></i> Modules</h2>
            <p class="section-desc">{{ $sitemap['modules']['description'] }}</p>
            <div class="route-cards">
                @foreach($sitemap['modules']['routes'] as $route)
                <div class="route-card module-card">
                    <div class="route-module">{{ $route['module'] ?? 'core' }}</div>
                    <div class="route-path">{{ $route['path'] }}</div>
                    <div class="route-name">{{ $route['name'] }}</div>
                    <div class="route-desc">{{ $route['description'] }}</div>
                    <span class="route-method method-{{ strtolower($route['method']) }}">{{ $route['method'] }}</span>
                    @if(isset($route['auth']) && $route['auth'])
                    <span class="route-auth"><i class="fas fa-lock"></i> Auth</span>
                    @endif
                </div>
                @endforeach
            </div>
        </section>

        <footer class="sitemap-footer">
            <p>Generated by AC Beneran v{{ $sitemap['app']['version'] }}</p>
            <p>Total Routes: {{ count($sitemap['public']['routes']) + count($sitemap['authenticated']['routes']) + count($sitemap['frontdesk_admin']['routes']) + count($sitemap['manager_admin']['routes']) + count($sitemap['admin']['routes']) + count($sitemap['technician']['routes']) + count($sitemap['viewer']['routes']) + count($sitemap['modules']['routes']) }}</p>
        </footer>
    </div>
</div>
@endsection

@push('styles')
<style>
.sitemap-container {
    min-height: 100vh;
    background: var(--bg-primary, #f8fafc);
}

.sitemap-header {
    background: linear-gradient(135deg, var(--primary, #2F5D50) 0%, var(--primary-dark, #1a3c34) 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.sitemap-header h1 {
    margin: 0 0 0.5rem;
    font-size: 2.5rem;
}

.sitemap-header .lead {
    opacity: 0.9;
    font-size: 1.1rem;
}

.header-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}

.sitemap-nav {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: sticky;
    top: 1rem;
    z-index: 100;
}

.sitemap-nav ul {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.sitemap-nav a {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    color: var(--text-primary, #1e293b);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.sitemap-nav a:hover {
    background: var(--primary, #2F5D50);
    color: white;
}

.sitemap-section {
    margin-bottom: 3rem;
}

.sitemap-section h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary, #1e293b);
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.sitemap-section h2 i {
    color: var(--primary, #2F5D50);
}

.section-desc {
    color: var(--text-secondary, #64748b);
    margin-bottom: 1.5rem;
}

.route-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.route-card {
    background: white;
    border-radius: 10px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-left: 4px solid var(--primary, #2F5D50);
    transition: transform 0.2s, box-shadow 0.2s;
}

.route-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.route-card.module-card {
    border-left-color: var(--accent, #10b981);
}

.route-path {
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.9rem;
    color: var(--primary, #2F5D50);
    font-weight: 600;
    margin-bottom: 0.5rem;
    word-break: break-all;
}

.route-name {
    font-size: 0.85rem;
    color: var(--text-secondary, #64748b);
    margin-bottom: 0.5rem;
}

.route-desc {
    color: var(--text-primary, #1e293b);
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
}

.route-method {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.method-get {
    background: #dbeafe;
    color: #1d4ed8;
}

.method-post {
    background: #dcfce7;
    color: #16a34a;
}

.method-put {
    background: #fef3c7;
    color: #d97706;
}

.method-delete {
    background: #fee2e2;
    color: #dc2626;
}

.route-module {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--accent, #10b981);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.route-auth {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary, #64748b);
    margin-left: 0.5rem;
}

.sitemap-footer {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary, #64748b);
    border-top: 1px solid var(--border, #e2e8f0);
    margin-top: 3rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-outline {
    background: transparent;
    border: 2px solid rgba(255,255,255,0.5);
    color: white;
}

.btn-outline:hover {
    background: white;
    color: var(--primary, #2F5D50);
}

@media print {
    .sitemap-nav {
        display: none;
    }

    .header-actions {
        display: none;
    }

    .route-card {
        break-inside: avoid;
    }
}

@media (max-width: 768px) {
    .route-cards {
        grid-template-columns: 1fr;
    }

    .sitemap-header h1 {
        font-size: 1.75rem;
    }
}
</style>
@endpush