// Hàm để tính chiều cao của header và đặt padding-top cho body
function adjustBodyPadding() {
    const header = document.querySelector('header');
    const body = document.querySelector('body');
    
    // Lấy chiều cao của header
    const headerHeight = header.offsetHeight;

    // Áp dụng padding-top cho body để tránh che khuất nội dung
    body.style.paddingTop = headerHeight + 'px';
}

// Gọi hàm khi trang được tải và khi cửa sổ thay đổi kích thước
window.addEventListener('load', adjustBodyPadding);
window.addEventListener('resize', adjustBodyPadding);