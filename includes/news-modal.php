<!-- News Details Modal (Facebook-Style Viewer) -->
<div class="modal fade" id="newsModal" tabindex="-1" role="dialog" aria-labelledby="newsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="newsModalLabel">
                    <i class="fas fa-newspaper me-2"></i><span id="modalNewsTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('close'); ?>"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- Loading Indicator -->
                <div id="newsLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php echo __('loading_article'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php echo __('loading_article'); ?></p>
                </div>
                
                <!-- Error Message -->
                <div id="newsErrorMessage" class="alert alert-danger d-none" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="errorText"></span>
                </div>
                
                <!-- News Content -->
                <div id="newsContent" class="d-none">
                    <!-- Category & Featured Badge -->
                    <div id="newsCategoryBadge" class="mb-2"></div>
                    
                    <!-- Article Meta -->
                    <div class="text-muted small mb-3">
                        <i class="fas fa-user me-1"></i><span id="newsAuthor"></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-calendar me-1"></i><span id="newsDate"></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-eye me-1"></i><span id="newsViews"></span> <?php echo __('views'); ?>
                    </div>
                    
                    <!-- Excerpt -->
                    <div id="newsExcerptDiv" class="lead text-muted mb-3 d-none">
                        <span id="newsExcerpt"></span>
                    </div>
                    
                    <!-- Main Content -->
                    <div id="newsArticleContent" class="article-content mb-4">
                        <span id="newsArticleText"></span>
                    </div>
                    
                    <!-- Article Footer Info -->
                    <div class="border-top pt-3 mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong><?php echo __('target_audience'); ?>:</strong>
                                    <span id="newsAudience" class="badge bg-info ms-2"></span>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong><?php echo __('priority'); ?>:</strong>
                                    <span id="newsPriority" class="badge ms-2"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Article Footer (Social) -->
                    <div id="modalSocialSection" class="border-top mt-4 pt-4 d-none">
                        <div class="d-flex align-items-center mb-3">
                            <button class="btn btn-sm me-3" id="modalLikeBtn">
                                <i class="fas fa-thumbs-up me-1"></i>
                                <span id="modalLikeText">Like</span>
                                (<span id="modalLikeCount">0</span>)
                            </button>
                            <span class="text-muted small">
                                <i class="fas fa-comment me-1"></i>
                                <span id="modalCommentCount">0</span> Comments
                            </span>
                        </div>

                        <!-- Comments Section -->
                        <div id="modalCommentsContainer">
                            <h6>Comments</h6>
                            
                            <!-- Comment Form -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="d-flex mb-3 mt-3">
                                    <div class="flex-shrink-0">
                                        <img src="<?php echo $_SESSION['profile_image'] ?? (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/assets/images/default-avatar.svg'; ?>" class="rounded-circle" width="32" height="32">
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <form id="modalCommentForm">
                                            <input type="hidden" name="news_id" id="modalNewsId">
                                            <input type="hidden" name="parent_id" id="modalParentId" value="">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="comment" class="form-control" placeholder="Write a comment..." required>
                                                <button type="submit" class="btn btn-primary">Post</button>
                                            </div>
                                            <div id="modalReplyingTo" class="small text-muted mt-1 d-none">
                                                Replying to <span id="modalReplyAuthor"></span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="cancelModalReplyBtn">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Comments List -->
                            <div id="modalCommentsList" class="mt-3" style="max-height: 400px; overflow-y: auto;">
                                <!-- Comments loaded via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Related News Section -->
                    <div id="relatedNewsSection" class="border-top mt-4 pt-4 d-none">
                        <h6 class="mb-3">
                            <i class="fas fa-newspaper me-2"></i><?php echo __('related_news'); ?>
                        </h6>
                        <div id="relatedNewsList" class="row g-2"></div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer border-top d-none" id="newsModalFooter">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i><?php echo __('close'); ?>
                </button>
                <a href="#" id="fullArticleLink" class="btn btn-primary btn-sm" target="_blank" rel="noopener" title="<?php echo __('view_full_article'); ?>">
                    <i class="fas fa-external-link-alt me-1"></i><?php echo __('view_full_article'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const NEWS_API_URL = '<?php echo defined("SITE_URL") ? rtrim(SITE_URL, '/') : ''; ?>/api/get-news-details.php';
const LIKE_API_URL = '<?php echo defined("SITE_URL") ? rtrim(SITE_URL, '/') : ''; ?>/api/like-news.php';
const COMMENT_API_URL = '<?php echo defined("SITE_URL") ? rtrim(SITE_URL, '/') : ''; ?>/api/add-comment.php';
const NEWS_DETAILS_PAGE_URL = '<?php echo defined("SITE_URL") ? rtrim(SITE_URL, '/') : ''; ?>/public/news_details.php';

// News Modal Handler
const newsModal = {
    modal: null,
    currentNewsId: null,
    
    init() {
        const modalElement = document.getElementById('newsModal');
        if (!modalElement) {
            console.error('News modal wrapper not found in DOM.');
            return;
        }
        this.modal = new bootstrap.Modal(modalElement);
        this.setupEventListeners();
    },
    
    setupEventListeners() {
        // Show modal when clicked
        document.addEventListener('click', (e) => {
            const newsLink = e.target.closest('[data-news-id]');
            if (newsLink) {
                e.preventDefault();
                const newsId = newsLink.getAttribute('data-news-id');
                this.show(newsId);
            }
        });

        // Comment form submission
        const commentForm = document.getElementById('modalCommentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCommentSubmit(new FormData(commentForm));
            });
        }

        // Cancel reply button
        const cancelReplyBtn = document.getElementById('cancelModalReplyBtn');
        if (cancelReplyBtn) {
            cancelReplyBtn.addEventListener('click', () => this.cancelReply());
        }
    },

    getElement(id) {
        return document.getElementById(id);
    },

    setText(id, text) {
        const element = this.getElement(id);
        if (!element) {
            console.error('Missing modal element:', id);
            return false;
        }
        element.textContent = text;
        return true;
    },

    setHtml(id, html) {
        const element = this.getElement(id);
        if (!element) {
            console.error('Missing modal element:', id);
            return false;
        }
        element.innerHTML = html;
        return true;
    },

    setHref(id, url) {
        const element = this.getElement(id);
        if (!element) {
            console.error('Missing modal element:', id);
            return false;
        }
        element.href = url;
        return true;
    },

    ensureModalReady(newsId) {
        if (!this.modal) {
            console.error('News modal is not initialized. Redirecting to detail page.');
            window.location.href = `${NEWS_DETAILS_PAGE_URL}?id=${encodeURIComponent(newsId)}`;
            return false;
        }
        return true;
    },
    
    show(newsId) {
        this.currentNewsId = newsId;
        if (!this.ensureModalReady(newsId)) {
            return;
        }
        this.resetModal();
        this.modal.show();
        this.loadNewsDetails(newsId);
    },
    
    resetModal() {
        const loadingSpinner = this.getElement('newsLoadingSpinner');
        const errorMessage = this.getElement('newsErrorMessage');
        const newsContent = this.getElement('newsContent');
        const newsModalFooter = this.getElement('newsModalFooter');
        const socialSection = this.getElement('modalSocialSection');
        const modalNewsIdInput = this.getElement('modalNewsId');

        if (loadingSpinner) loadingSpinner.classList.remove('d-none');
        if (errorMessage) errorMessage.classList.add('d-none');
        if (newsContent) newsContent.classList.add('d-none');
        if (newsModalFooter) newsModalFooter.classList.add('d-none');
        if (socialSection) socialSection.classList.add('d-none');
        if (modalNewsIdInput) modalNewsIdInput.value = '';
        this.cancelReply();
    },
    
    loadNewsDetails(newsId) {
        fetch(`${NEWS_API_URL}?id=${encodeURIComponent(newsId)}`, { credentials: 'same-origin' })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || '<?php echo __('error_loading_article'); ?>');
                    });
                }
                return response.json();
            })
            .then(response => {
                if (response.success) {
                    this.displayNews(response.data);
                } else {
                    throw new Error(response.error || '<?php echo __('error_loading_article'); ?>');
                }
            })
            .catch(error => {
                console.error('Error loading news:', error);
                window.location.href = `${NEWS_DETAILS_PAGE_URL}?id=${encodeURIComponent(newsId)}`;
            });
    },
    
    displayNews(news) {
        const requiredIds = [
            'newsModalLabel', 'modalNewsTitle', 'newsCategoryBadge', 'newsAuthor',
            'newsDate', 'newsViews', 'newsExcerpt', 'newsArticleText', 'newsAudience',
            'newsPriority', 'fullArticleLink', 'newsLoadingSpinner',
            'newsErrorMessage', 'newsContent', 'newsModalFooter'
        ];

        for (const id of requiredIds) {
            if (!this.getElement(id)) {
                console.error('Missing modal element:', id);
                this.showError('<?php echo __('error_loading_article'); ?>');
                return;
            }
        }

        // Update modal title
        if (!this.setText('newsModalLabel', news.title)) return;
        if (!this.setText('modalNewsTitle', news.title)) return;
        
        // Set category badge
        let categoryHtml = '';
        if (news.category_name) {
            categoryHtml = `<span class="badge" style="background-color: ${news.category_color};">${news.category_name}</span>`;
        }
        if (news.featured) {
            categoryHtml += ` <span class="badge bg-warning ms-1"><i class="fas fa-star me-1"></i><?php echo __('featured'); ?></span>`;
        }
        if (categoryHtml) {
            if (!this.setHtml('newsCategoryBadge', categoryHtml)) return;
        }
        
        // Set article meta
        if (!this.setText('newsAuthor', news.author_name)) return;
        if (!this.setText('newsDate', new Date(news.publication_date).toLocaleDateString('<?php echo get_current_language() === "am" ? "am-ET" : (get_current_language() === "ti" ? "ti-ET" : "en-US"); ?>'))) return;
        if (!this.setText('newsViews', news.view_count.toLocaleString())) return;
        
        // Set excerpt
        if (news.excerpt) {
            if (!this.setText('newsExcerpt', news.excerpt)) return;
            const excerptDiv = this.getElement('newsExcerptDiv');
            if (excerptDiv) {
                excerptDiv.classList.remove('d-none');
            }
        }
        
        // Set article content
        const contentHtml = this.renderContent(news.content);
        if (!this.setHtml('newsArticleText', contentHtml)) return;
        
        // Set footer info
        if (!this.setText('newsAudience', news.target_audience.toUpperCase())) return;
        
        const priorityColors = {
            'low': 'secondary',
            'medium': 'primary',
            'high': 'warning',
            'urgent': 'danger'
        };
        const priorityColor = priorityColors[news.priority] || 'secondary';
        const newsPriorityEl = this.getElement('newsPriority');
        if (!newsPriorityEl) return;
        newsPriorityEl.className = `badge bg-${priorityColor}`;
        newsPriorityEl.textContent = news.priority.toUpperCase();
        
        // Set full article link
        if (!this.setHref('fullArticleLink', `${NEWS_DETAILS_PAGE_URL}?id=${encodeURIComponent(news.news_id)}`)) return;
        
        // Social Data
        this.updateSocialData(news);
        const modalNewsIdInput = this.getElement('modalNewsId');
        if (modalNewsIdInput) modalNewsIdInput.value = news.news_id;
        
        // Show related news
        if (news.related_news && news.related_news.length > 0) {
            this.displayRelatedNews(news.related_news);
            const relatedSection = this.getElement('relatedNewsSection');
            if (relatedSection) {
                relatedSection.classList.remove('d-none');
            }
        }
        
        // Show content
        const loadingSpinner = this.getElement('newsLoadingSpinner');
        const newsContent = this.getElement('newsContent');
        const newsModalFooter = this.getElement('newsModalFooter');
        const socialSection = this.getElement('modalSocialSection');
        
        if (loadingSpinner) loadingSpinner.classList.add('d-none');
        if (newsContent) newsContent.classList.remove('d-none');
        if (newsModalFooter) newsModalFooter.classList.remove('d-none');
        if (socialSection) socialSection.classList.remove('d-none');
    },

    updateSocialData(news) {
        const likeBtn = this.getElement('modalLikeBtn');
        const likeText = this.getElement('modalLikeText');
        const likeCount = this.getElement('modalLikeCount');
        const commentCount = this.getElement('modalCommentCount');
        const commentsList = this.getElement('modalCommentsList');
        
        if (likeCount) likeCount.textContent = news.like_count || 0;
        if (commentCount) commentCount.textContent = news.comment_count || 0;
        
        if (likeBtn && likeText) {
            if (news.is_liked) {
                likeBtn.className = 'btn btn-primary btn-sm me-3';
                likeText.textContent = 'Liked';
            } else {
                likeBtn.className = 'btn btn-outline-primary btn-sm me-3';
                likeText.textContent = 'Like';
            }
            likeBtn.onclick = () => this.handleLike(news.news_id);
        }

        if (commentsList) {
            if (news.allow_comments) {
                const commentsContainer = this.getElement('modalCommentsContainer');
                if (commentsContainer) commentsContainer.style.display = 'block';
                commentsList.innerHTML = (news.comments && news.comments.length > 0) 
                    ? this.renderComments(news.comments) 
                    : '<p class="text-center text-muted small py-3">No comments yet.</p>';
            } else {
                const commentsContainer = this.getElement('modalCommentsContainer');
                if (commentsContainer) commentsContainer.style.display = 'none';
            }
        }
    },

    handleLike(newsId) {
        fetch(LIKE_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `news_id=${newsId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const likeBtn = this.getElement('modalLikeBtn');
                const likeText = this.getElement('modalLikeText');
                const likeCount = this.getElement('modalLikeCount');
                
                if (likeCount) likeCount.textContent = data.like_count;
                if (likeBtn && likeText) {
                    if (data.action === 'liked') {
                        likeBtn.className = 'btn btn-primary btn-sm me-3';
                        likeText.textContent = 'Liked';
                    } else {
                        likeBtn.className = 'btn btn-outline-primary btn-sm me-3';
                        likeText.textContent = 'Like';
                    }
                }
            }
        });
    },

    handleCommentSubmit(formData) {
        fetch(COMMENT_API_URL, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const form = this.getElement('modalCommentForm');
                if (form) form.reset();
                this.cancelReply();
                // Refresh news details to show new comment
                this.loadNewsDetails(formData.get('news_id'));
            }
        });
    },

    renderComments(comments, isReply = false) {
        return comments.map(comment => {
            const avatar = comment.profile_image ? (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') + '/' + comment.profile_image : (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') + '/assets/images/default-avatar.svg';
            const date = new Date(comment.created_at).toLocaleDateString();
            
            return `
                <div class="d-flex ${isReply ? 'mt-2' : 'mb-3'}" id="modal-comment-${comment.comment_id}">
                    <div class="flex-shrink-0">
                        <img src="${avatar}" class="rounded-circle" width="${isReply ? '24' : '32'}" height="${isReply ? '24' : '32'}">
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <div class="bg-light p-2 rounded small">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">${comment.full_name}</span>
                                <span class="text-muted" style="font-size: 0.75rem;">${date}</span>
                            </div>
                            <p class="mb-0">${comment.comment}</p>
                        </div>
                        <div class="mt-1" style="font-size: 0.75rem;">
                            <button class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.75rem;" 
                                    onclick="newsModal.prepareReply(${comment.comment_id}, '${comment.full_name.replace(/'/g, "\\'")}')">Reply</button>
                        </div>
                        ${comment.replies && comment.replies.length > 0 ? `
                            <div class="ms-3 border-start ps-2 mt-2">
                                ${this.renderComments(comment.replies, true)}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    },

    prepareReply(id, author) {
        const parentIdInput = this.getElement('modalParentId');
        const replyAuthorSpan = this.getElement('modalReplyAuthor');
        const replyingToDiv = this.getElement('modalReplyingTo');
        const commentInput = document.querySelector('#modalCommentForm input[name="comment"]');

        if (parentIdInput) parentIdInput.value = id;
        if (replyAuthorSpan) replyAuthorSpan.textContent = author;
        if (replyingToDiv) replyingToDiv.classList.remove('d-none');
        if (commentInput) commentInput.focus();
    },

    cancelReply() {
        const parentIdInput = this.getElement('modalParentId');
        const replyingToDiv = this.getElement('modalReplyingTo');
        if (parentIdInput) parentIdInput.value = '';
        if (replyingToDiv) replyingToDiv.classList.add('d-none');
    },
    
    renderContent(content) {
        if (!content) {
            return '<p class="text-muted"><?php echo __('no_additional_details'); ?></p>';
        }
        
        // Check if content has HTML
        const hasHtml = /<\s*\w+/.test(content);
        if (hasHtml) {
            // Sanitize HTML (basic escaping of script tags)
            return content.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        } else {
            // Plain text - preserve line breaks
            return content.split('\n').map(line => `<p>${line.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>`).join('');
        }
    },
    
    displayRelatedNews(relatedNews) {
        const listHtml = relatedNews.map(news => `
            <div class="col-md-6 mb-2">
                <div class="card h-100 cursor-pointer" data-news-id="${news.news_id}" style="cursor: pointer;">
                    <div class="card-body">
                        <h6 class="card-title mb-2">${news.title}</h6>
                        ${news.excerpt ? `<p class="card-text small text-muted">${news.excerpt.substring(0, 50)}...</p>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        const relatedNewsList = this.getElement('relatedNewsList');
        if (relatedNewsList) {
            relatedNewsList.innerHTML = listHtml;
        }
    },
    
    showError(errorMessage) {
        const loadingSpinner = this.getElement('newsLoadingSpinner');
        const errorMessageEl = this.getElement('newsErrorMessage');
        const errorText = this.getElement('errorText');

        if (loadingSpinner) loadingSpinner.classList.add('d-none');
        if (errorMessageEl) errorMessageEl.classList.remove('d-none');
        if (errorText) errorText.textContent = errorMessage;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        newsModal.init();
    });
} else {
    newsModal.init();
}
</script>
