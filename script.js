// Menu Highlighting
const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item => {
    const li = item.parentElement;

    item.addEventListener('click', () => {
        allSideMenu.forEach(i => i.parentElement.classList.remove('active'));
        li.classList.add('active');
    });
});

// Toggle Sidebar
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', () => {
    sidebar.classList.toggle('hide');
});

// Search Bar Toggle for Small Screens
const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', (e) => {
    if (window.innerWidth < 576) {
        e.preventDefault();
        searchForm.classList.toggle('show');
        searchButtonIcon.classList.toggle('bx-search');
        searchButtonIcon.classList.toggle('bx-x');
    }
});

// Responsive Behavior on Window Resize
const handleResize = () => {
    if (window.innerWidth < 768) {
        sidebar.classList.add('hide');
    }
    if (window.innerWidth > 576) {
        searchButtonIcon.classList.remove('bx-x');
        searchButtonIcon.classList.add('bx-search');
        searchForm.classList.remove('show');
    }
};

// Initial Check
handleResize();

window.addEventListener('resize', handleResize);

// Dynamic Profile Image
const profileImage = document.getElementById('profile-image');

// Fetch the user's initials from the server
fetch('/api/user/initials')
    .then(response => response.json())
    .then(data => {
        const userInitials = data.initials; // Retrieve initials from the API response
        profileImage.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userInitials)}&background=random&color=fff`;
    })
    .catch(error => {
        console.error('Error fetching user initials:', error);
        // Optional: Set a default image or handle the error as needed
        profileImage.src = 'https://ui-avatars.com/api/?name=Default&background=random&color=fff';
    });

// Initialize FullCalendar with Bakuna Day events and reminders
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            // Example events, replace with actual vaccination schedule data
            {
                title: 'Vaccination 1',
                start: '2024-08-25'
            },
            {
                title: 'Vaccination 2',
                start: '2024-09-10'
            },
            // Recurring Bakuna Day every Wednesday
            ...Array.from({ length: 52 }).map((_, i) => {
                const date = new Date();
                date.setFullYear(2024); // Change to current year if needed
                date.setMonth(0); // January
                date.setDate(1 + (i * 7) + (2 - date.getDay() + 7) % 7); // Every Wednesday
                return {
                    title: 'Bakuna Day',
                    start: date.toISOString().split('T')[0],
                    color: 'red' // Optional: Set a color for the event
                };
            }),
            // Sample Reminders
            {
                title: 'Reminder: Check Vaccination Records',
                start: '2024-08-30',
                color: 'blue' // Optional: Set a color for the reminder
            },
            {
                title: 'Reminder: Schedule Appointment',
                start: '2024-09-05',
                color: 'green' // Optional: Set a color for the reminder
            },
            {
                title: 'Reminder: Review Vaccination Status',
                start: '2024-09-15',
                color: 'orange' // Optional: Set a color for the reminder
            }
        ]
    });
    calendar.render();
});
