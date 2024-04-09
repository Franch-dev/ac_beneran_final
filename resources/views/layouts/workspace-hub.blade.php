@if(!empty($showWorkspaceHub) && !empty($workspaceHubLinks) && !empty($workspaceContext))
    <div class="page-container">
        <section class="service-hub glass-surface" data-aos="fade-up" data-aos-delay="40" style="margin-bottom: 28px;">
            <div class="service-hub__header">
                <div>
                    <span class="ops-hero__eyebrow">Workspace Hub</span>
                    <h2 class="ops-section-title">{{ $workspaceContext['label'] }}</h2>
                    <p class="ops-section-copy">Akses cepat lintas halaman tanpa bergantung pada sidebar. Pilih kartu yang Anda perlukan dari hub kerja aktif ini.</p>
                </div>
                <div class="ops-chip-row">
                    <span class="ops-chip">
                        <i class="{{ $workspaceContext['icon'] }}"></i>
                        {{ $workspaceContext['hub_label'] }}
                    </span>
                    <span class="ops-chip">
                        <i class="fas fa-user-shield"></i>
                        {{ auth()->user()->roleLabel() }}
                    </span>
                </div>
            </div>

            <div class="service-hub__grid">
                @foreach($workspaceHubLinks as $link)
                    <a href="{{ $link['url'] }}" class="service-hub__tile {{ $link['active'] ? 'is-active' : '' }}">
                        <i class="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endif
