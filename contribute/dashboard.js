// OmniGrid Creator Dashboard
const Dashboard = {
    streams: [],
    
    async init() {
        await this.loadStreams();
        this.bindEvents();
    },
    
    bindEvents() {
        // Add stream form
        document.getElementById('addStreamForm')?.addEventListener('submit', e => {
            e.preventDefault();
            this.saveStream();
        });
        
        // Revenue mode toggle
        document.getElementById('revenueMode')?.addEventListener('change', e => {
            const override = e.target.value === 'override';
            document.getElementById('priceGroup').style.display = override ? 'block' : 'none';
            document.getElementById('multiplierGroup').style.display = override ? 'none' : 'block';
        });
        
        // Thumbnail upload
        document.getElementById('thumbFile')?.addEventListener('change', e => {
            if (e.target.files[0]) this.uploadThumb(e.target.files[0]);
        });
        
        // Get location button
        document.getElementById('getLocation')?.addEventListener('click', () => this.getLocation());
    },
    
    async loadStreams() {
        try {
            const res = await fetch('get_streams.php');
            const data = await res.json();
            if (data.success) {
                this.streams = data.streams;
                this.renderStreams();
            }
        } catch (err) {
            this.toast('Failed to load streams', 'error');
        }
    },
    
    renderStreams() {
        const container = document.getElementById('streamsList');
        if (!container) return;
        
        if (!this.streams.length) {
            container.innerHTML = '<div class="empty-state"><i class="fa fa-video-slash"></i><p>No streams yet. Add your first one above.</p></div>';
            return;
        }
        
        container.innerHTML = this.streams.map(s => `
            <div class="stream-card ${s.is_active ? 'active' : 'inactive'}" data-id="${s.id}">
                <div class="stream-thumb">
                    ${s.thumb_url ? `<img src="../${s.thumb_url}" alt="">` : '<div class="no-thumb"><i class="fa fa-image"></i></div>'}
                    <span class="stream-type type-${s.type}">${s.type}</span>
                </div>
                <div class="stream-info">
                    <h3>${this.esc(s.title)}</h3>
                    <span class="vibe-tag">${this.esc(s.vibe_tag || 'no tag')}</span>
                    <div class="stream-stats">
                        <span><i class="fa fa-eye"></i> ${this.formatNum(s.views)}</span>
                        <span><i class="fa fa-users"></i> ${s.subs_count} subs</span>
                        <span><i class="fa fa-dollar-sign"></i> ${this.formatMoney(s.total_earnings_cents)}</span>
                    </div>
                    <div class="stream-meta">
                        <span class="mode-badge ${s.revenue_mode}">${s.revenue_mode === 'smartgrid' ? `smartGrid ${s.smartgrid_multiplier}x` : `$${s.price_per_minute}/min`}</span>
                        ${s.archive_enabled ? '<span class="archive-on"><i class="fa fa-archive"></i> Archive</span>' : ''}
                    </div>
                </div>
                <div class="stream-actions">
                    <a href="../broadcast.html?room=${s.id}" class="action-btn" title="Go Live" target="_blank">
                        <i class="fa fa-broadcast-tower"></i>
                    </a>
                    <button onclick="Dashboard.toggleActive(${s.id}, ${s.is_active ? 0 : 1})" title="${s.is_active ? 'Pause' : 'Activate'}">
                        <i class="fa fa-${s.is_active ? 'pause' : 'play'}"></i>
                    </button>
                    <button onclick="Dashboard.editStream(${s.id})" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button onclick="Dashboard.deleteStream(${s.id})" title="Delete" class="danger">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },
    
    async saveStream(streamId = null) {
        const form = document.getElementById('addStreamForm');
        const data = {
            title: form.title.value.trim(),
            type: form.type.value,
            vibe_tag: form.vibe_tag.value.trim(),
            revenue_mode: form.revenue_mode.value,
            price_per_minute: parseFloat(form.price_per_minute.value) || 0.01,
            smartgrid_multiplier: parseFloat(form.smartgrid_multiplier.value) || 1.0,
            geo_lat: form.geo_lat.value ? parseFloat(form.geo_lat.value) : null,
            geo_lng: form.geo_lng.value ? parseFloat(form.geo_lng.value) : null,
            archive_enabled: form.archive_enabled.checked,
            thumb_url: form.thumb_url.value
        };
        
        if (!data.title) {
            this.toast('Title is required', 'error');
            return;
        }
        
        const endpoint = streamId ? 'update_stream.php' : 'save_stream.php';
        if (streamId) data.id = streamId;
        
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if (result.success) {
                this.toast(streamId ? 'Stream updated' : 'Stream created', 'success');
                form.reset();
                this.closeModal();
                await this.loadStreams();
            } else {
                this.toast(result.error || 'Failed to save', 'error');
            }
        } catch (err) {
            this.toast('Network error', 'error');
        }
    },
    
    async toggleActive(id, active) {
        try {
            const res = await fetch('update_stream.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, is_active: active})
            });
            const result = await res.json();
            if (result.success) {
                this.toast(active ? 'Stream is live' : 'Stream paused', 'success');
                await this.loadStreams();
            }
        } catch (err) {
            this.toast('Failed to update', 'error');
        }
    },
    
    editStream(id) {
        const stream = this.streams.find(s => s.id === id);
        if (!stream) return;
        
        const form = document.getElementById('addStreamForm');
        form.title.value = stream.title || '';
        form.type.value = stream.type;
        form.vibe_tag.value = stream.vibe_tag || '';
        form.revenue_mode.value = stream.revenue_mode;
        form.price_per_minute.value = stream.price_per_minute;
        form.smartgrid_multiplier.value = stream.smartgrid_multiplier;
        form.geo_lat.value = stream.geo_lat || '';
        form.geo_lng.value = stream.geo_lng || '';
        form.archive_enabled.checked = stream.archive_enabled == 1;
        form.thumb_url.value = stream.thumb_url || '';
        
        // Update visibility
        const override = stream.revenue_mode === 'override';
        document.getElementById('priceGroup').style.display = override ? 'block' : 'none';
        document.getElementById('multiplierGroup').style.display = override ? 'none' : 'block';
        
        // Show thumb preview if exists
        if (stream.thumb_url) {
            document.getElementById('thumbPreview').innerHTML = `<img src="../${stream.thumb_url}" alt="">`;
        }
        
        // Switch form to edit mode
        form.dataset.editId = id;
        document.getElementById('formTitle').textContent = 'Edit Stream';
        document.getElementById('submitBtn').textContent = 'Update Stream';
        
        this.openModal();
    },
    
    async deleteStream(id) {
        if (!confirm('Delete this stream? This cannot be undone.')) return;
        
        try {
            const res = await fetch('delete_stream.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            });
            const result = await res.json();
            if (result.success) {
                this.toast('Stream deleted', 'success');
                await this.loadStreams();
            } else {
                this.toast(result.error || 'Failed to delete', 'error');
            }
        } catch (err) {
            this.toast('Network error', 'error');
        }
    },
    
    async uploadThumb(file) {
        const formData = new FormData();
        formData.append('thumb', file);
        
        document.getElementById('thumbPreview').innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        try {
            const res = await fetch('upload_thumb.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                document.getElementById('thumb_url').value = result.thumb_url;
                document.getElementById('thumbPreview').innerHTML = `<img src="../${result.thumb_url}" alt="">`;
                this.toast('Thumbnail uploaded', 'success');
            } else {
                document.getElementById('thumbPreview').innerHTML = '';
                this.toast(result.error || 'Upload failed', 'error');
            }
        } catch (err) {
            document.getElementById('thumbPreview').innerHTML = '';
            this.toast('Upload failed', 'error');
        }
    },
    
    getLocation() {
        if (!navigator.geolocation) {
            this.toast('Geolocation not supported', 'error');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('geo_lat').value = pos.coords.latitude.toFixed(6);
                document.getElementById('geo_lng').value = pos.coords.longitude.toFixed(6);
                this.toast('Location captured', 'success');
            },
            err => this.toast('Could not get location', 'error')
        );
    },
    
    openModal() {
        document.getElementById('streamModal').classList.add('open');
    },
    
    closeModal() {
        document.getElementById('streamModal').classList.remove('open');
        const form = document.getElementById('addStreamForm');
        form.reset();
        delete form.dataset.editId;
        document.getElementById('formTitle').textContent = 'Add New Stream';
        document.getElementById('submitBtn').textContent = 'Create Stream';
        document.getElementById('thumbPreview').innerHTML = '';
    },
    
    toast(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fa fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${msg}`;
        document.getElementById('toasts').appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    },
    
    esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
    
    formatNum(n) {
        if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n/1000).toFixed(1) + 'K';
        return n || 0;
    },
    
    formatMoney(cents) {
        return '$' + ((cents || 0) / 100).toFixed(2);
    }
};

document.addEventListener('DOMContentLoaded', () => Dashboard.init());
