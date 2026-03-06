// Interactive JavaScript for Social Network UI Mockup

document.addEventListener('DOMContentLoaded', function() {
    // Initialize interactive elements
    
    // Feed action buttons
    const feedActionButtons = document.querySelectorAll('.feed-action-btn');
    feedActionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const span = this.querySelector('span');
            
            // Toggle active state
            if (icon.classList.contains('fa-thumbs-up')) {
                if (this.classList.contains('active')) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.classList.remove('active');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.classList.add('active');
                    this.style.color = '#634F9C';
                }
            }
        });
    });
    
    // Nav icon buttons
    const navIconButtons = document.querySelectorAll('.nav-icon-btn');
    navIconButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            navIconButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
        });
    });
    
    // Sidebar items
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active class from all items
            sidebarItems.forEach(sidebarItem => sidebarItem.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
        });
    });
    
    // Chat items
    const chatItems = document.querySelectorAll('.chat-item');
    chatItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            chatItems.forEach(chatItem => chatItem.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
        });
    });
    
    // Post input focus effect
    const postInput = document.querySelector('.post-input');
    if (postInput) {
        postInput.addEventListener('focus', function() {
            this.parentElement.parentElement.style.boxShadow = '0 8px 24px rgba(99, 79, 156, 0.15)';
        });
        
        postInput.addEventListener('blur', function() {
            this.parentElement.parentElement.style.boxShadow = '';
        });
    }
    
    // Search bar interactions
    const searchInputs = document.querySelectorAll('.search-bar input, .chat-search input');
    searchInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Feed menu buttons
    const feedMenuButtons = document.querySelectorAll('.feed-menu-btn');
    feedMenuButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            // Add rotation animation
            this.style.transform = 'rotate(90deg)';
            setTimeout(() => {
                this.style.transform = 'rotate(0deg)';
            }, 300);
        });
    });
    
    // Action buttons hover effects
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });
    });
    
    // Smooth scroll for chat list
    const chatList = document.querySelector('.chat-list');
    if (chatList) {
        chatList.addEventListener('scroll', function() {
            // Add subtle parallax effect
            const scrollTop = this.scrollTop;
            const chatItems = this.querySelectorAll('.chat-item');
            chatItems.forEach((item, index) => {
                const offset = (scrollTop - index * 80) * 0.1;
                item.style.transform = `translateY(${offset}px)`;
            });
        });
    }
    
    // Add loading animation to feed cards
    const feedCards = document.querySelectorAll('.feed-card');
    feedCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // User profile nav dropdown simulation
    const userProfileNav = document.querySelector('.user-profile-nav');
    if (userProfileNav) {
        userProfileNav.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.style.transform = icon.style.transform === 'rotate(180deg)' ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    }
    
    // Create post card interactions
    const createPostCard = document.querySelector('.create-post-card');
    if (createPostCard) {
        createPostCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        createPostCard.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
    
    // Add ripple effect to buttons
    function addRippleEffect(element) {
        element.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }
    
    // Apply ripple effect to interactive buttons
    const interactiveButtons = document.querySelectorAll('.feed-action-btn, .action-btn, .nav-icon-btn');
    interactiveButtons.forEach(button => {
        addRippleEffect(button);
    });
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(99, 79, 156, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .feed-action-btn,
    .action-btn,
    .nav-icon-btn {
        position: relative;
        overflow: hidden;
    }
`;
document.head.appendChild(style);

