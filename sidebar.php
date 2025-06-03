<?php
    $curPageName = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);

    $col = [
        ["label" => "Trang chủ", "route" => "home(ad).php"],
            ["label" => "Quản lý sách", "route" => "books.php"],
            ["label" => "Quản lý danh mục", "route" => "categories.php"],
            ["label" => "Quản lý nhân viên", "route" => "employees.php"],
            ["label" => "Quản lý ưu đãi", "route" => "discounts.php"],
            ["label" => "Báo cáo", "route" => "reports.php"],
            ["label" => "Đăng xuất", "route" => "login.php"],
    ];
?>

<div class="w-full max-w-[300px]">
    <div class="bg-slate-900 w-full h-screen text-center py-8 overflow-hidden">
        <div class="flex gap-4 items-center justify-center">
            <img src="./images/demo.jpeg" height="50" width="50" alt="Admin Logo" />
            <h1 class="text-2xl text-white">ADMIN</h1>
        </div>
        <div class="text-left mt-8">
            <?php foreach ($col as $item): 
                $url = $item['route'];
                $isActive = strstr($url, $curPageName);
            ?>
                <a href="<?php echo $url; ?>" class="<?php echo $isActive ? 'text-white italic' : 'text-gray-400' ?> p-4 mb-2 flex items-center hover:text-white cursor-pointer duration-300 outline-none w-full relative">
                    <?php echo $isActive ? '- ' : ''?>
                    <?php echo $item["label"]; ?>
                    <div class="<?php echo $isActive ? 'bg-white' : ''?> absolute w-6 h-6 top-1/2 -right-3 -translate-y-1/2 rotate-45"></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
