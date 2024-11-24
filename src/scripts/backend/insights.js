document.addEventListener('DOMContentLoaded', function() {
    const accountSelector = document.getElementById('account-selector');
    const periodSelector = document.getElementById('period-selector');
    const dateRangeText = document.getElementById('date-range-text');
    let followersChart = null;
    let engagementChart = null;

    // Initialize Charts
    function initCharts() {
        const followersCtx = document.getElementById('followers-chart').getContext('2d');
        const engagementCtx = document.getElementById('engagement-chart').getContext('2d');

        // Common chart options
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };

        // Initialize Followers Chart
        followersChart = new Chart(followersCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: wpzoomInsights.i18n.followers,
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: chartOptions
        });

        // Initialize Engagement Chart
        engagementChart = new Chart(engagementCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: wpzoomInsights.i18n.reach,
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    },
                    {
                        label: wpzoomInsights.i18n.impressions,
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        tension: 0.1
                    }
                ]
            },
            options: chartOptions
        });
    }

    function getDateRange(period) {
        const endDate = new Date();
        let startDate = new Date();

        switch(period) {
            case 'this_month':
                startDate = new Date(endDate.getFullYear(), endDate.getMonth(), 1);
                break;
            case 'last_month':
                startDate = new Date(endDate.getFullYear(), endDate.getMonth() - 1, 1);
                endDate.setDate(0); // Last day of previous month
                break;
            default:
                startDate.setDate(endDate.getDate() - parseInt(period));
        }

        return {
            since: startDate.toISOString().split('T')[0],
            until: endDate.toISOString().split('T')[0]
        };
    }

    function updateDateRangeDisplay(since, until) {
        const formatDate = (dateStr) => {
            const date = new Date(dateStr);
            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        };

        dateRangeText.textContent = wpzoomInsights.i18n.dateRangeFormat
            .replace('%s', formatDate(since))
            .replace('%s', formatDate(until));
    }

    // Update metrics display
    function updateMetrics(data) {
        // Update follower stats
        if (data.followers_stats) {
            const stats = data.followers_stats;
            
            // Display total followers
            document.getElementById('followers-count').textContent = formatNumber(stats.total);
            
            // Display followers change
            const changeText = formatNumber(Math.abs(stats.change)) + ' followers ' + 
                (stats.change >= 0 ? 'gained' : 'lost');
            
            const percentageText = ' (' + 
                (stats.change >= 0 ? '+' : '-') + 
                Math.abs(stats.change_percentage).toFixed(1) + '%)';
            
            document.getElementById('followers-change').textContent = changeText + percentageText;
            updateChangeClass('followers-change', stats.change);
            
            // Update period info
            document.getElementById('followers-period').textContent = 
                'Started with ' + formatNumber(stats.period_start) + 
                ' followers, ended with ' + formatNumber(stats.period_end);
        }

        // Update reach
        if (data.reach) {
            const latestReach = data.reach[data.reach.length - 1].value;
            document.getElementById('reach-count').textContent = formatNumber(latestReach);
            
            const previousReach = data.reach[0].value;
            const reachChange = calculateChange(previousReach, latestReach);
            document.getElementById('reach-change').textContent = formatChange(reachChange);
            updateChangeClass('reach-change', reachChange);
        }

        // Update impressions
        if (data.impressions) {
            const latestImpressions = data.impressions[data.impressions.length - 1].value;
            document.getElementById('impressions-count').textContent = formatNumber(latestImpressions);
            
            const previousImpressions = data.impressions[0].value;
            const impressionsChange = calculateChange(previousImpressions, latestImpressions);
            document.getElementById('impressions-change').textContent = formatChange(impressionsChange);
            updateChangeClass('impressions-change', impressionsChange);
        }

        // Update engagement (accounts_engaged)
        if (data.accounts_engaged) {
            const engagementValue = data.accounts_engaged[0].value; // Using first value since it's a total
            document.getElementById('engagement-count').textContent = formatNumber(engagementValue);
            document.getElementById('engagement-change').textContent = '-'; // No change calculation for total value
        }

        // Update profile metrics
        if (data.profile_views) {
            const latestViews = data.profile_views[data.profile_views.length - 1].value;
            document.getElementById('profile-views-count').textContent = formatNumber(latestViews);
            
            const previousViews = data.profile_views[0].value;
            const viewsChange = calculateChange(previousViews, latestViews);
            document.getElementById('profile-views-change').textContent = formatChange(viewsChange);
            updateChangeClass('profile-views-change', viewsChange);
        }

        // Update recent media
        if (data.recent_media) {
            updateRecentMedia(data.recent_media);
        }

        // Update charts
        updateCharts(data);
    }

    function updateRecentMedia(media) {
        const container = document.getElementById('recent-posts');
        container.innerHTML = '';

        media.forEach(post => {
            const postElement = document.createElement('div');
            postElement.className = 'recent-post';
            
            // Prepare video-specific stats
            const videoStats = post.type === 'VIDEO' || post.type === 'REEL' ? `
                <div class="stat">
                    <span class="label">Video Views:</span>
                    <span class="value">${formatNumber(post.insights.video_views || 0)}</span>
                </div>
                <div class="stat">
                    <span class="label">Plays:</span>
                    <span class="value">${formatNumber(post.insights.plays || 0)}</span>
                </div>
            ` : '';

            postElement.innerHTML = `
                <div class="post-thumbnail">
                    <img src="${post.thumbnail}" alt="${post.caption}">
                </div>
                <div class="post-content">
                    <div class="post-caption">${post.caption}</div>
                    <div class="post-stats">
                        <div class="stat">
                            <span class="label">Impressions:</span>
                            <span class="value">${formatNumber(post.insights.impressions || 0)}</span>
                        </div>
                        <div class="stat">
                            <span class="label">Reach:</span>
                            <span class="value">${formatNumber(post.insights.reach || 0)}</span>
                        </div>
                        <div class="stat">
                            <span class="label">Likes:</span>
                            <span class="value">${formatNumber(post.likes || 0)}</span>
                        </div>
                        <div class="stat">
                            <span class="label">Comments:</span>
                            <span class="value">${formatNumber(post.comments || 0)}</span>
                        </div>
                        <div class="stat">
                            <span class="label">Saved:</span>
                            <span class="value">${formatNumber(post.insights.saved || 0)}</span>
                        </div>
                        ${videoStats}
                        <div class="stat">
                            <span class="label">Total Interactions:</span>
                            <span class="value">${formatNumber(post.insights.total_interactions || 0)}</span>
                        </div>
                    </div>
                    <div class="post-meta">
                        <a href="${post.url}" target="_blank" class="view-post">View Post</a>
                        <span class="post-date">${formatDate(post.timestamp)}</span>
                    </div>
                </div>
            `;
            
            container.appendChild(postElement);
        });
    }

    // Update chart data
    function updateCharts(data) {
        if (!data.reach || !data.impressions) return;

        const dates = data.reach.map(item => formatDate(item.end_time));
        const reachData = data.reach.map(item => item.value);
        const impressionsData = data.impressions.map(item => item.value);
        
        // Update Followers Chart if we have follower data
        if (data.follower_count) {
            followersChart.data.labels = dates;
            followersChart.data.datasets[0].data = data.follower_count.map(item => item.value);
            followersChart.update();
        }

        // Update Engagement Chart
        engagementChart.data.labels = dates;
        engagementChart.data.datasets[0].data = reachData;
        engagementChart.data.datasets[1].data = impressionsData;
        engagementChart.update();
    }

    // Helper functions
    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    function formatChange(change) {
        return (change >= 0 ? '+' : '') + change.toFixed(1) + '%';
    }

    function calculateChange(previous, current) {
        if (previous === 0) return 0;
        return ((current - previous) / previous) * 100;
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    function updateChangeClass(elementId, change) {
        const element = document.getElementById(elementId);
        element.classList.remove('positive', 'negative');
        element.classList.add(change >= 0 ? 'positive' : 'negative');
    }

    // Update fetchInsights to include date range
    function fetchInsights(accountId, period) {
        const dateRange = getDateRange(period);
        
        jQuery.ajax({
            url: wpzoomInsights.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpzoom_instagram_fetch_insights',
                nonce: wpzoomInsights.nonce,
                account_id: accountId,
                since_date: dateRange.since,
                until_date: dateRange.until
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateDateRangeDisplay(dateRange.since, dateRange.until);
                    updateMetrics(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching insights:', error);
            }
        });
    }

    // Initialize everything
    if (accountSelector && periodSelector) {
        initCharts();
        
        // Initial fetch
        fetchInsights(accountSelector.value, periodSelector.value);

        // Event listeners
        accountSelector.addEventListener('change', function() {
            fetchInsights(this.value, periodSelector.value);
        });

        periodSelector.addEventListener('change', function() {
            fetchInsights(accountSelector.value, this.value);
        });
    }
}); 