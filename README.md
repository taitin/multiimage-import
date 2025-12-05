# Dcat Admin Multi-Image Import Extension

Laravel Dcat Admin 的多圖片匯入擴充套件,支援批次上傳圖片和 Excel 檔案匯入。

## 功能特點

- 📦 支援 ZIP 檔案批次上傳
- 🖼️ 支援多圖片上傳(最多 100 個檔案)
- 📊 整合 Excel 匯入功能
- 🔄 自動清理 24 小時前的臨時檔案
- 👥 支援多使用者同時上傳(避免檔案路徑衝突)
- 🛡️ 唯一 ID 生成機制,防止檔案覆蓋

## 安裝

```bash
composer require taitin/multiimage-import
```

## 使用方法

在你的 Dcat Admin Controller 中使用 `MultiImageImportTool`:

```php
use Taitin\MultiimageImport\Tools\MultiImageImportTool;

$grid->tools(function ($tools) {
    $tools->append(new MultiImageImportTool(
        YourImportClass::class,
        '/path/to/sample.xlsx',
        '/admin/your-route'
    ));
});
```

## 版本歷史

### v1.0.4 (2025-12-05)
- 🐛 修正 `ZipArchive::extractTo()` Invalid or uninitialized Zip object 錯誤
- ✅ 在解壓縮前檢查 ZIP 檔案是否成功開啟
- 📝 提供詳細的 ZIP 檔案錯誤訊息
- 📁 自動建立解壓縮目錄

### v1.0.3 (2025-12-05)
- 🐛 修正檔案上傳衝突導致的 `errno=21 Is a directory` 錯誤
- ✨ 改進 `setId()` 方法,總是生成唯一 ID(時間戳記 + 微秒)
- 🧹 新增自動清理機制,刪除超過 24 小時的臨時目錄
- 👥 防止多使用者檔案路徑衝突

### v1.0.2
- 修正多個問題

### v1.0.1
- 更新

### v1.0.0
- 初始版本

## 技術細節

### 唯一 ID 生成
每次上傳都會生成唯一的時間戳記 ID (格式: `{timestamp}{microseconds}`),確保不同使用者之間不會發生檔案路徑衝突。

### 自動清理機制
系統會在每次開啟匯入表單時,自動清理超過 24 小時的臨時目錄,防止儲存空間被佔滿。

## 授權

MIT License

## 作者

Tim (tim0407@gmail.com)
