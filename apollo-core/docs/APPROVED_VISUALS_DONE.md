# Approved Visuals — Definition of Done

## Acceptance Checklist for Each Refactored Template

### Hero Section
- [ ] Video/YouTube embed loads and **autoplay + muted + loop + no controls**
- [ ] Overlay gradient matches approved design (transparent to black)
- [ ] Title, date, time, location display correctly
- [ ] Event tag pills (if present) show proper styling

### Quick Actions
- [ ] 4 buttons in grid layout: Tickets, Line-up, Route, Interest
- [ ] Icons and labels match approved design
- [ ] Hover effects work correctly

### RSVP Avatars
- [ ] Shows real user avatars from `_apollo_interested_user_ids` (not random/fake)
- [ ] Avatar count displays correct total
- [ ] Interest toggle updates avatars dynamically
- [ ] No duplicate IDs (especially `changingword`)

### Info Section
- [ ] Event description displays from post content
- [ ] Music tags marquee animates smoothly
- [ ] All text properly escaped

### Promo Gallery
- [ ] 5 images from featured + attached images
- [ ] Slider controls work (prev/next)
- [ ] Auto-advance every 4 seconds

### Lineup Section
- [ ] DJ cards show photo/initials, name, time slots
- [ ] Links to DJ profiles work
- [ ] Time display format matches approved design

### Venue Section
- [ ] 5 venue images in slider with dots navigation
- [ ] Auto-advance carousel works
- [ ] Address displays correctly

### Map Section
- [ ] **Leaflet OSM tiles render over container** (not static background image)
- [ ] Route input accepts user location
- [ ] Google Maps directions open in new tab

### Tickets Section
- [ ] External ticket links work with `?ref=apollo.rio.br`
- [ ] Coupon code displays and copies correctly
- [ ] Guest list link (if present) works

### Bottom Bar
- [ ] **Exactly 2 buttons**: Tickets/Acessos (left) + Share (right)
- [ ] Tickets button scrolls to tickets section
- [ ] Share button uses Web Share API or clipboard fallback
- [ ] Word animation cycles through languages
- [ ] Fixed positioning at bottom

### General
- [ ] `uni.css` loads from `https://assets.apollo.rio.br/uni.css`
- [ ] `base.js` loads from `https://assets.apollo.rio.br/base.js`
- [ ] No SCSS nesting in CSS (`&:hover` converted to `.class:hover`)
- [ ] No console errors
- [ ] Mobile responsive (centered container on desktop)
- [ ] All dynamic content escaped and sanitized
- [ ] No duplicate IDs or classes
- [ ] Performance: no unnecessary re-renders or heavy operations

## Testing URLs
- Local development URLs for each template type
- Test with various data scenarios (missing images, long text, many DJs, etc.)
- Cross-browser testing (Chrome, Firefox, Safari, mobile)

## Automated Checks
- PHP syntax validation
- CSS validity (no SCSS nesting)
- HTML structure matches approved template
- Asset loading verification
- Basic functionality tests (buttons, links, forms)
