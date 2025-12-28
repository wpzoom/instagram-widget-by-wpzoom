/**
 * Instagram Insights Admin Page
 *
 * Handles the UI interactions for the Instagram Insights dashboard
 * including chart rendering, data fetching, and metric displays.
 *
 * @package suspended
 * @since 2.0.0
 */

import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Legend,
    Tooltip
} from 'chart.js';

// Register Chart.js components
Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Legend,
    Tooltip
);

document.addEventListener('DOMContentLoaded', function() {
    const accountSelector = document.getElementById('account-selector');
    const periodSelector = document.getElementById('period-selector');
    const dateRangeText = document.getElementById('date-range-text');
    const insightsContainer = document.querySelector('.insights-container');
    const followersChartNote = document.getElementById('followers-chart-note');

    let followersChart = null;
    let engagementChart = null;
    let isLoading = false;
    let isLoadingMore = false;
    let dailyFollowerChanges = []; // Store daily changes for tooltip display
    let currentMediaCursor = ''; // Store the pagination cursor for Load More

    /**
     * Show loading state on the page
     */
    function showLoading() {
        if (isLoading) return;
        isLoading = true;

        // Add loading class to container
        if (insightsContainer) {
            insightsContainer.classList.add('is-loading');
        }

        // Show skeleton loaders on metric cards
        const metricValues = document.querySelectorAll('.metric-value');
        metricValues.forEach(el => {
            el.classList.add('skeleton');
            el.setAttribute('data-original', el.textContent);
            el.textContent = '';
        });

        const metricChanges = document.querySelectorAll('.metric-change');
        metricChanges.forEach(el => {
            el.classList.add('skeleton');
            el.setAttribute('data-original', el.textContent);
            el.textContent = '';
        });

        // Show loading overlay on charts
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            if (!container.querySelector('.chart-loading-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'chart-loading-overlay';
                overlay.innerHTML = '<div class="loading-spinner"></div>';
                container.appendChild(overlay);
            }
        });

        // Show loading state on recent posts
        const recentPosts = document.getElementById('recent-posts');
        if (recentPosts) {
            recentPosts.classList.add('is-loading');
            if (!recentPosts.querySelector('.posts-loading-placeholder')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'posts-loading-placeholder';
                placeholder.innerHTML = `
                    <div class="post-skeleton"></div>
                    <div class="post-skeleton"></div>
                    <div class="post-skeleton"></div>
                `;
                recentPosts.appendChild(placeholder);
            }
        }
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        isLoading = false;

        if (insightsContainer) {
            insightsContainer.classList.remove('is-loading');
        }

        // Remove skeleton class from metrics
        const skeletons = document.querySelectorAll('.skeleton');
        skeletons.forEach(el => {
            el.classList.remove('skeleton');
        });

        // Remove chart loading overlays
        const overlays = document.querySelectorAll('.chart-loading-overlay');
        overlays.forEach(el => el.remove());

        // Remove posts loading placeholder
        const placeholder = document.querySelector('.posts-loading-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        const recentPosts = document.getElementById('recent-posts');
        if (recentPosts) {
            recentPosts.classList.remove('is-loading');
        }
    }

    /**
     * Show error message
     * @param {string} message - Error message to display
     */
    function showError(message) {
        hideLoading();

        // Remove any existing error messages
        const existingError = document.querySelector('.insights-error');
        if (existingError) {
            existingError.remove();
        }

        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'insights-error notice notice-error';
        errorDiv.innerHTML = `
            <p><strong>${wpzoomInsights.i18n.error || 'Error'}:</strong> ${message}</p>
            <button type="button" class="notice-dismiss" aria-label="${wpzoomInsights.i18n.dismiss || 'Dismiss'}">
                <span class="screen-reader-text">${wpzoomInsights.i18n.dismiss || 'Dismiss this notice.'}</span>
            </button>
        `;

        // Insert after the header
        const header = document.querySelector('.insights-header');
        if (header) {
            header.after(errorDiv);
        } else if (insightsContainer) {
            insightsContainer.prepend(errorDiv);
        }

        // Handle dismiss button
        const dismissBtn = errorDiv.querySelector('.notice-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                errorDiv.remove();
            });
        }

        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.classList.add('fade-out');
                setTimeout(() => errorDiv.remove(), 300);
            }
        }, 10000);
    }

    /**
     * Clear any error messages
     */
    function clearErrors() {
        const errors = document.querySelectorAll('.insights-error');
        errors.forEach(el => el.remove());
    }

    /**
     * Initialize Charts
     */
    function initCharts() {
        const followersCtx = document.getElementById('followers-chart');
        const engagementCtx = document.getElementById('engagement-chart');

        if (!followersCtx || !engagementCtx) {
            return;
        }

        // Common chart options
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };

        // Initialize Follower Growth Chart
        // Shows actual follower count over time (like Instagram's native chart)
        followersChart = new Chart(followersCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: wpzoomInsights.i18n.followerGrowth || 'Follower growth',
                    data: [],
                    borderColor: 'rgb(66, 133, 244)',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(66, 133, 244)',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.parsed.y;
                                const index = context.dataIndex;
                                const dailyChange = dailyFollowerChanges[index] || 0;
                                const changePrefix = dailyChange >= 0 ? '+' : '';

                                return [
                                    `${wpzoomInsights.i18n.followers || 'Followers'}: ${formatNumber(total)}`,
                                    `${changePrefix}${dailyChange} ${wpzoomInsights.i18n.followersChange || 'followers'}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            // Only show integer values (no decimals like 1988.5)
                            precision: 0,
                            callback: function(value) {
                                // Skip non-integer values
                                if (value !== Math.floor(value)) {
                                    return null;
                                }
                                return formatCompactNumber(value);
                            }
                        }
                    }
                }
            }
        });

        // Initialize Reach Chart (formerly Engagement Chart)
        // Note: 'views' metric doesn't support time_series, so we only show reach
        engagementChart = new Chart(engagementCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: wpzoomInsights.i18n.reach,
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: chartOptions
        });
    }

    /**
     * Calculate date range based on period selection
     * @param {string} period - Period value (7, 14, 30, 90, this_month, last_month)
     * @returns {Object} Object with since and until dates
     */
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

    /**
     * Update the date range display text
     * @param {string} since - Start date
     * @param {string} until - End date
     */
    function updateDateRangeDisplay(since, until) {
        if (!dateRangeText) return;

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

    /**
     * Update metrics display with data
     * @param {Object} data - Insights data from API
     */
    function updateMetrics(data) {
        // Update follower stats
        if (data.followers_stats) {
            const stats = data.followers_stats;

            // Display total followers
            const followersCount = document.getElementById('followers-count');
            if (followersCount) {
                followersCount.textContent = formatNumber(stats.total);
            }
        }

        // Update new/lost/net followers breakdown
        if (data.new_followers && data.new_followers.length > 0) {
            const newFollowersEl = document.getElementById('new-followers');
            if (newFollowersEl) {
                const newValue = data.new_followers[0].value;
                newFollowersEl.textContent = '+' + formatNumber(newValue);
            }
        }

        if (data.lost_followers && data.lost_followers.length > 0) {
            const lostFollowersEl = document.getElementById('lost-followers');
            if (lostFollowersEl) {
                const lostValue = data.lost_followers[0].value;
                lostFollowersEl.textContent = '-' + formatNumber(lostValue);
            }
        }

        if (data.net_followers && data.net_followers.length > 0) {
            const netFollowersEl = document.getElementById('net-followers');
            if (netFollowersEl) {
                const netValue = data.net_followers[0].value;
                netFollowersEl.textContent = (netValue >= 0 ? '+' : '') + formatNumber(netValue);
                netFollowersEl.classList.remove('positive', 'negative');
                if (netValue > 0) {
                    netFollowersEl.classList.add('positive');
                } else if (netValue < 0) {
                    netFollowersEl.classList.add('negative');
                }
            }
        }

        // Update reach
        if (data.reach && data.reach.length > 0) {
            const totalReach = data.reach.reduce((sum, item) => sum + item.value, 0);
            const reachCount = document.getElementById('reach-count');
            if (reachCount) {
                reachCount.textContent = formatNumber(totalReach);
            }

            // Calculate trend
            if (data.reach.length > 1) {
                const midpoint = Math.floor(data.reach.length / 2);
                const firstHalf = data.reach.slice(0, midpoint).reduce((sum, item) => sum + item.value, 0);
                const secondHalf = data.reach.slice(midpoint).reduce((sum, item) => sum + item.value, 0);
                const reachChange = calculateChange(firstHalf, secondHalf);

                const reachChangeEl = document.getElementById('reach-change');
                if (reachChangeEl) {
                    reachChangeEl.textContent = formatChange(reachChange);
                    updateChangeClass('reach-change', reachChange);
                }
            }
        }

        // Update views (formerly impressions)
        // Note: views metric only returns total_value, not time_series
        if (data.impressions && data.impressions.length > 0) {
            const totalViews = data.impressions.reduce((sum, item) => sum + item.value, 0);
            const impressionsCount = document.getElementById('impressions-count');
            if (impressionsCount) {
                impressionsCount.textContent = formatNumber(totalViews);
            }

            const impressionsChangeEl = document.getElementById('impressions-change');
            if (impressionsChangeEl) {
                impressionsChangeEl.textContent = wpzoomInsights.i18n.totalPeriod || 'Total for period';
                impressionsChangeEl.classList.remove('positive', 'negative');
            }
        }

        // Update engagement (accounts_engaged)
        if (data.accounts_engaged && data.accounts_engaged.length > 0) {
            const engagementValue = data.accounts_engaged[0].value;
            const engagementCount = document.getElementById('engagement-count');
            if (engagementCount) {
                engagementCount.textContent = formatNumber(engagementValue);
            }

            const engagementChange = document.getElementById('engagement-change');
            if (engagementChange) {
                engagementChange.textContent = wpzoomInsights.i18n.totalPeriod || 'Total for period';
                engagementChange.classList.remove('positive', 'negative');
            }
        }

        // Update total likes
        if (data.likes && data.likes.length > 0) {
            const totalLikes = data.likes.reduce((sum, item) => sum + item.value, 0);
            const likesCount = document.getElementById('total-likes-count');
            if (likesCount) {
                likesCount.textContent = formatNumber(totalLikes);
            }

            const likesChange = document.getElementById('total-likes-change');
            if (likesChange) {
                likesChange.textContent = wpzoomInsights.i18n.totalPeriod || 'Total for period';
                likesChange.classList.remove('positive', 'negative');
            }
        }

        // Update recent media with pagination cursor
        if (data.recent_media) {
            updateRecentMedia(data.recent_media, data.media_next_cursor || '');
        }

        // Update charts
        updateCharts(data);
    }

    /**
     * Create HTML for a single post element
     * @param {Object} post - Post data
     * @returns {HTMLElement} Post element
     */
    function createPostElement(post) {
        const postElement = document.createElement('div');
        postElement.className = 'recent-post';

        // Prepare video-specific stats
        const videoStats = (post.type === 'VIDEO' || post.type === 'REEL') ? `
            <div class="stat">
                <span class="label">${wpzoomInsights.i18n.videoViews || 'Video Views'}:</span>
                <span class="value">${formatNumber(post.insights.video_views || 0)}</span>
            </div>
        ` : '';

        postElement.innerHTML = `
            <div class="post-thumbnail">
                <img src="${escapeHtml(post.thumbnail)}" alt="${escapeHtml(post.caption)}" loading="lazy">
                ${post.type === 'VIDEO' || post.type === 'REEL' ? '<span class="media-type-badge">VIDEO</span>' : ''}
            </div>
            <div class="post-content">
                <div class="post-caption">${escapeHtml(post.caption)}</div>
                <div class="post-stats">
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.impressionsLabel || 'Impressions'}:</span>
                        <span class="value">${formatNumber(post.insights.impressions || 0)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.reachLabel || 'Reach'}:</span>
                        <span class="value">${formatNumber(post.insights.reach || 0)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.likes || 'Likes'}:</span>
                        <span class="value">${formatNumber(post.likes || 0)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.comments || 'Comments'}:</span>
                        <span class="value">${formatNumber(post.comments || 0)}</span>
                    </div>
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.saved || 'Saved'}:</span>
                        <span class="value">${formatNumber(post.insights.saved || 0)}</span>
                    </div>
                    ${videoStats}
                    <div class="stat">
                        <span class="label">${wpzoomInsights.i18n.interactions || 'Total Interactions'}:</span>
                        <span class="value">${formatNumber(post.insights.total_interactions || 0)}</span>
                    </div>
                </div>
                <div class="post-meta">
                    <a href="${escapeHtml(post.url)}" target="_blank" rel="noopener noreferrer" class="view-post">
                        ${wpzoomInsights.i18n.viewPost || 'View Post'}
                    </a>
                    <span class="post-date">${formatDate(post.timestamp)}</span>
                </div>
            </div>
        `;

        return postElement;
    }

    /**
     * Update recent media posts display
     * @param {Array} media - Array of media items
     * @param {string} nextCursor - Pagination cursor for next page
     */
    function updateRecentMedia(media, nextCursor = '') {
        const container = document.getElementById('recent-posts');
        if (!container) return;

        container.innerHTML = '';

        // Store the cursor for Load More
        currentMediaCursor = nextCursor;

        if (!media || media.length === 0) {
            container.innerHTML = `<p class="no-posts">${wpzoomInsights.i18n.noPosts || 'No recent posts found.'}</p>`;
            return;
        }

        media.forEach(post => {
            container.appendChild(createPostElement(post));
        });

        // Add Load More button if there are more posts
        updateLoadMoreButton(container);
    }

    /**
     * Update or create the Load More button
     * @param {HTMLElement} container - The posts container
     */
    function updateLoadMoreButton(container) {
        // Remove existing Load More wrapper if present
        const existingWrapper = container.parentElement.querySelector('.load-more-wrapper');
        if (existingWrapper) {
            existingWrapper.remove();
        }

        // Only add button if there's a cursor for more posts
        if (!currentMediaCursor) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'load-more-wrapper';
        wrapper.innerHTML = `
            <button type="button" class="button button-primary load-more-button">
                ${wpzoomInsights.i18n.loadMore || 'Load More Posts'}
            </button>
        `;

        // Insert after the posts container
        container.parentElement.appendChild(wrapper);

        // Attach click handler
        const button = wrapper.querySelector('.load-more-button');
        button.addEventListener('click', handleLoadMore);
    }

    /**
     * Handle Load More button click
     */
    function handleLoadMore() {
        if (isLoadingMore || !currentMediaCursor) return;

        const button = document.querySelector('.load-more-button');
        if (!button) return;

        isLoadingMore = true;
        const originalText = button.textContent;
        button.textContent = wpzoomInsights.i18n.loading || 'Loading...';
        button.disabled = true;

        jQuery.ajax({
            url: wpzoomInsights.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpzoom_instagram_load_more_posts',
                nonce: wpzoomInsights.nonce,
                account_id: accountSelector.value,
                cursor: currentMediaCursor
            },
            success: function(response) {
                isLoadingMore = false;

                if (response.success && response.data) {
                    // Update cursor for next load
                    currentMediaCursor = response.data.next_cursor || '';

                    // Append new posts
                    const container = document.getElementById('recent-posts');
                    if (container && response.data.posts) {
                        response.data.posts.forEach(post => {
                            container.appendChild(createPostElement(post));
                        });
                    }

                    // Update Load More button
                    if (currentMediaCursor) {
                        button.textContent = originalText;
                        button.disabled = false;
                    } else {
                        // No more posts, remove the button
                        const wrapper = document.querySelector('.load-more-wrapper');
                        if (wrapper) {
                            wrapper.remove();
                        }
                    }
                } else {
                    button.textContent = wpzoomInsights.i18n.noMorePosts || 'No more posts to load.';
                    button.disabled = true;
                    setTimeout(() => {
                        const wrapper = document.querySelector('.load-more-wrapper');
                        if (wrapper) {
                            wrapper.remove();
                        }
                    }, 2000);
                }
            },
            error: function() {
                isLoadingMore = false;
                button.textContent = originalText;
                button.disabled = false;
                showError(wpzoomInsights.i18n.networkError || 'Network error occurred. Please try again.');
            }
        });
    }

    /**
     * Calculate cumulative follower counts from daily changes
     * The API returns daily change values which may only track gains.
     * We scale the cumulative values to match the actual period_start and period_end.
     * @param {Array} dailyChanges - Array of {end_time, value} with daily change values
     * @param {number} periodStart - The actual follower count at the start of the period
     * @param {number} periodEnd - The actual follower count at the end of the period (current total)
     * @returns {Array} Array of cumulative follower counts scaled to actual values
     */
    function calculateCumulativeFollowers(dailyChanges, periodStart, periodEnd) {
        // First, calculate raw cumulative values from daily changes
        let rawCumulative = 0;
        const rawValues = dailyChanges.map(item => {
            rawCumulative += item.value;
            return rawCumulative;
        });

        // If no changes or single day, return flat line at current total
        if (rawValues.length === 0) {
            return [];
        }

        const rawTotal = rawValues[rawValues.length - 1];
        const actualChange = periodEnd - periodStart;

        // If raw total is 0 or matches actual change, no scaling needed
        if (rawTotal === 0 || rawTotal === actualChange) {
            return rawValues.map((val, index) => {
                return periodStart + (rawTotal === 0 ? 0 : val);
            });
        }

        // Scale the values proportionally so they match actual start and end
        // This preserves the shape of daily fluctuations while ensuring accuracy
        const scaleFactor = actualChange / rawTotal;
        return rawValues.map(val => {
            return Math.round(periodStart + (val * scaleFactor));
        });
    }

    /**
     * Update chart data
     * @param {Object} data - Insights data
     */
    function updateCharts(data) {
        if (!followersChart || !engagementChart) return;

        // Update Follower Growth Chart
        // The API returns daily changes, so we calculate cumulative counts
        if (data.follower_count && data.follower_count.length > 0 && data.followers_stats) {
            const labels = data.follower_count.map(item => formatDate(item.end_time));

            // Store daily changes for tooltip display
            dailyFollowerChanges = data.follower_count.map(item => item.value);

            // Calculate cumulative follower counts scaled to actual period_start and period_end
            const periodStart = data.followers_stats.period_start || 0;
            const periodEnd = data.followers_stats.period_end || data.followers_stats.total || 0;
            const cumulativeData = calculateCumulativeFollowers(data.follower_count, periodStart, periodEnd);

            // Update tick formatter based on data range
            const smartFormatter = createSmartTickFormatter(cumulativeData);
            followersChart.options.scales.y.ticks.callback = smartFormatter;

            followersChart.data.labels = labels;
            followersChart.data.datasets[0].data = cumulativeData;
            followersChart.update();
        } else {
            // Clear the chart when no data is available (e.g., switching to account without insights)
            dailyFollowerChanges = [];
            followersChart.data.labels = [];
            followersChart.data.datasets[0].data = [];
            followersChart.update();
        }

        // Update Reach Chart
        // Note: 'views' metric doesn't support time_series, so we only show reach
        if (data.reach && data.reach.length > 0) {
            const dates = data.reach.map(item => formatDate(item.end_time));
            const reachData = data.reach.map(item => item.value);

            engagementChart.data.labels = dates;
            engagementChart.data.datasets[0].data = reachData;
            engagementChart.update();
        } else {
            // Clear the reach chart when no data is available
            engagementChart.data.labels = [];
            engagementChart.data.datasets[0].data = [];
            engagementChart.update();
        }
    }

    // Helper functions

    /**
     * Format a number with locale-specific formatting
     * @param {number} num - Number to format
     * @returns {string} Formatted number
     */
    function formatNumber(num) {
        if (num === null || num === undefined) return '-';
        return new Intl.NumberFormat().format(num);
    }

    /**
     * Format a number in compact notation (1k, 2.5k, 1M, etc.)
     * @param {number} num - Number to format
     * @param {boolean} forceExact - If true, show exact number without compact notation
     * @returns {string} Formatted compact number
     */
    function formatCompactNumber(num, forceExact = false) {
        if (num === null || num === undefined) return '-';

        if (forceExact) {
            return new Intl.NumberFormat().format(num);
        }

        if (num >= 1000000) {
            return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
        }
        return num.toString();
    }

    /**
     * Create a smart tick formatter that uses exact numbers when range is small
     * @param {Array} data - The chart data array
     * @returns {function} Tick callback function
     */
    function createSmartTickFormatter(data) {
        if (!data || data.length === 0) {
            return (value) => formatCompactNumber(value);
        }

        const min = Math.min(...data);
        const max = Math.max(...data);
        const range = max - min;

        // If the range is less than 5% of the max value, use exact numbers
        // This prevents all ticks showing as "2k" when values are 1990-2010
        const useExact = range < (max * 0.05) || range < 100;

        return (value) => formatCompactNumber(value, useExact);
    }

    /**
     * Format a change percentage
     * @param {number} change - Change value
     * @returns {string} Formatted change string
     */
    function formatChange(change) {
        if (change === null || change === undefined) return '-';
        return (change >= 0 ? '+' : '') + change.toFixed(1) + '%';
    }

    /**
     * Calculate percentage change between two values
     * @param {number} previous - Previous value
     * @param {number} current - Current value
     * @returns {number} Percentage change
     */
    function calculateChange(previous, current) {
        if (previous === 0) return current > 0 ? 100 : 0;
        return ((current - previous) / previous) * 100;
    }

    /**
     * Format a date string
     * @param {string} dateString - ISO date string
     * @returns {string} Formatted date
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Update CSS class based on positive/negative change
     * @param {string} elementId - Element ID
     * @param {number} change - Change value
     */
    function updateChangeClass(elementId, change) {
        const element = document.getElementById(elementId);
        if (!element) return;

        element.classList.remove('positive', 'negative');
        if (change > 0) {
            element.classList.add('positive');
        } else if (change < 0) {
            element.classList.add('negative');
        }
    }

    /**
     * Fetch insights data from the server
     * @param {string} accountId - Account ID
     * @param {string} period - Period value
     */
    function fetchInsights(accountId, period) {
        if (!accountId) {
            showError(wpzoomInsights.i18n.noAccount || 'No account selected.');
            return;
        }

        const dateRange = getDateRange(period);

        // Show/hide the follower chart note based on period
        // Instagram API limits follower_count data to last 30 days
        const periodDays = parseInt(period) || 30;
        if (followersChartNote) {
            followersChartNote.style.display = periodDays > 30 ? 'block' : 'none';
        }

        clearErrors();
        showLoading();

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
                hideLoading();

                if (response.success && response.data) {
                    updateDateRangeDisplay(dateRange.since, dateRange.until);
                    updateMetrics(response.data);
                } else {
                    const errorMessage = response.data || wpzoomInsights.i18n.fetchError || 'Failed to fetch insights data.';
                    showError(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();

                let errorMessage = wpzoomInsights.i18n.networkError || 'Network error occurred. Please try again.';

                if (xhr.status === 0) {
                    errorMessage = wpzoomInsights.i18n.connectionError || 'Could not connect to server. Please check your connection.';
                } else if (xhr.status === 403) {
                    errorMessage = wpzoomInsights.i18n.permissionError || 'You do not have permission to access this data.';
                } else if (xhr.status === 500) {
                    errorMessage = wpzoomInsights.i18n.serverError || 'Server error occurred. Please try again later.';
                }

                showError(errorMessage);
                console.error('Insights fetch error:', status, error);
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
