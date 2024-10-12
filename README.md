# Import Users & Products Stock Quantities

### Version 1.7

**Author**: Mohammad Kazem Gholian  
**Plugin URI**: [Import Users & Products Stock Quantities Plugin](https://valiasrcs.com/fa/how-to-transfer-easrp-users)

## Description

The **Import Users & Products Stock Quantities** plugin allows you to seamlessly import EASRP users and product stock quantities from an Excel file into your WordPress site. This plugin is built to ensure compatibility with the **Digits** plugin for mobile-based registration, making the process of updating user and product data efficient and smooth.

## Features

- **User Import:** Imports users from an Excel file, ensuring Digits plugin compatibility.
- **Product Stock Import:** Updates product stock quantities based on SKU.
- **Mobile Number Validation:** Normalizes mobile numbers for compatibility with the Digits plugin.
- **AJAX-based Chunked Import:** Avoids server timeouts with progress bar and chunked import process.
- **Custom Progress Tracking:** Shows progress with a real-time progress bar on both user and product imports.

## Installation

1. Download the plugin zip file or clone the repository.
2. Upload the plugin files to your `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

### Import Users

1. Go to `Tools > Import Users from Excel`.
2. Upload an Excel file (.xlsx) with users.
3. Click the "Upload and Import" button.
4. Monitor the progress through the progress bar.

### Import Product Stock Quantities

1. Go to `Tools > Import Products from Excel`.
2. Upload an Excel file (.xlsx) with product stock information.
3. Click the "Upload and Import Products" button.
4. Progress will be displayed in real-time via a progress bar.

## File Format

- **User Import File:** The Excel file should contain columns in the following order:
  1. Display Name
  2. Mobile Number
  3. Rank (Used for calculating the discount)

- **Product Stock Import File:** The Excel file should contain columns in the following order:
  1. SKU
  2. Main Stock
  3. Warehouse Stock

## Development & Contributions

Feel free to submit issues or feature requests using GitHub Issues. Contributions are welcome via pull requests. Please ensure that your changes are tested before submission.

## License

This plugin is licensed under the GPL-2.0 License. You can read the full license [here](LICENSE).

## Support

For further assistance, visit the [plugin page](https://valiasrcs.com/fa/how-to-transfer-easrp-users) or contact the author at [Mohammad Kazem Gholian](https://valiasrcs.com).
