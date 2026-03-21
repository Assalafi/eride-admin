# Parts & Inventory Management System - Complete Guide

## Overview
The Parts & Inventory Management system provides comprehensive tracking and management of vehicle parts across multiple branches. Each part has a picture, description, branch assignment, and stock tracking.

## Database Structure

### Parts Table
```
id              - Primary key
branch_id       - Foreign key to branches (parts are branch-specific)
name            - Part name
sku             - Stock Keeping Unit (unique identifier)
picture         - Path to part image (required)
description     - Detailed description of the part
cost            - Part cost in Naira
created_at      - Timestamp
updated_at      - Timestamp
```

### Part Stock Table
```
id              - Primary key
part_id         - Foreign key to parts
branch_id       - Foreign key to branches
quantity        - Current stock quantity
created_at      - Timestamp
updated_at      - Timestamp
UNIQUE (part_id, branch_id) - One stock record per part per branch
```

## Features

### 1. **Part Management**

#### Create New Part
**Requirements:**
- Branch selection (mandatory)
- Part name (mandatory)
- SKU - Stock Keeping Unit (mandatory, unique)
- Part picture (mandatory, JPG/PNG, max 2MB)
- Description (optional)
- Cost in Naira (mandatory)
- Initial quantity (mandatory, can be 0)

**Process:**
1. Navigate to Parts & Inventory → Add New Part
2. Fill in all required fields
3. Upload part picture
4. Set initial quantity for the selected branch
5. Submit form
6. System automatically:
   - Saves part with uploaded image
   - Creates initial stock record if quantity > 0
   - Stores picture in `storage/app/public/parts/`

#### Update Part
**Capabilities:**
- Change branch assignment
- Update name, SKU, cost
- Replace part picture (optional)
- Modify description
- **Note:** Stock quantity is updated separately via Stock In function

**Picture Handling:**
- Leave picture field blank to keep current image
- Upload new picture to replace old one
- Old picture automatically deleted when replaced
- Current picture displayed during edit

#### Delete Part
**Process:**
- Deletes part record
- Removes associated stock records
- Deletes picture file from storage
- Cannot be undone

### 2. **Stock Management**

#### Initial Stock
- Set during part creation
- Creates stock record for the selected branch
- Can be set to 0 for parts without initial inventory

#### Stock In (Add Stock)
**Access:** Stock In button on each part row

**Process:**
1. Click Stock In button for specific part
2. Modal displays current stock quantity
3. Enter quantity to add
4. Submit to increment stock
5. Updates existing stock or creates new record

**Logic:**
```php
// If stock record exists for this part + branch
   Increment quantity by added amount
// Else
   Create new stock record with added amount
```

#### Stock Display
Each part shows:
- **Current quantity** for user's branch
- **Status badge:**
  - 🟢 Green "In Stock" - quantity > 10
  - 🟡 Yellow "Low Stock" - quantity 1-10
  - 🔴 Red "Out of Stock" - quantity = 0

### 3. **Branch-Specific Features**

#### Part-Branch Relationship
- Each part belongs to one branch
- Parts can have stock in multiple branches
- Branch managers see only their branch's parts by default
- Super Admin sees all parts from all branches

#### Stock Per Branch
- Independent stock tracking per branch
- Same part can have different stock in different branches
- Stock In adds to specific branch's inventory
- Maintenance requests deduct from requesting branch's stock

### 4. **Statistics Dashboard**

Four real-time statistics cards:

**1. Total Parts**
- Count of all parts in system
- Includes all branches (Super Admin) or current branch

**2. In Stock**
- Parts with quantity > 10
- Indicates healthy inventory levels
- Green indicator

**3. Low Stock**
- Parts with quantity 1-10
- Alerts for reordering needed
- Yellow warning indicator

**4. Out of Stock**
- Parts with 0 quantity or no stock record
- Critical attention needed
- Red danger indicator

### 5. **Visual Features**

#### Part Pictures
- Required for all parts
- 50x50px thumbnail in listing
- Full size in edit form
- Object-fit cover for proper display
- Placeholder icon if no picture

#### Table Display
Columns:
- # - Serial number (paginated correctly)
- Picture - 50x50px thumbnail
- Part Name - Bold display
- SKU - Secondary badge
- Branch - Branch name
- Cost - Formatted currency (₦)
- Stock - Quantity badge with color coding
- Status - Status badge
- Actions - Edit, Stock In, Delete buttons

### 6. **Permissions & Access Control**

**View Inventory Permission:**
- Required to access parts list
- View part details
- See stock levels

**Manage Inventory Permission:**
- Edit parts
- Delete parts
- Add stock (Stock In)
- Full inventory control

**Super Admin:**
- All permissions
- See all branches
- Manage system-wide inventory

**Branch Manager/Staff:**
- See only their branch's parts
- Manage their branch's inventory
- Cannot access other branches' parts

## Integration with Other Modules

### 1. **Maintenance Requests**
When maintenance is completed:
```php
foreach ($maintenanceRequest->parts as $part) {
    // Deduct from branch stock
    $stock = PartStock::where([
        'part_id' => $part->id,
        'branch_id' => $request->branch_id
    ])->first();
    
    $stock->decrement('quantity', $pivot->quantity);
}
```

### 2. **Daily Ledger**
Track parts usage:
- Parts used per vehicle
- Parts cost deducted from driver balance
- Maintenance expense tracking

## Usage Examples

### Example 1: Create Brake Pad Part
```
Branch: Main Branch
Name: Front Brake Pad Set
SKU: BP-FRONT-001
Picture: brake-pad.jpg (uploaded)
Description: OEM replacement brake pads for front wheels
Cost: ₦15,000
Initial Quantity: 20
```

**Result:**
- Part created with ID #1
- Picture saved to storage/app/public/parts/
- Stock record: Part #1, Main Branch, Quantity 20
- Appears in inventory list immediately

### Example 2: Low Stock Alert
```
Part: Oil Filter
Branch: North Branch
Current Stock: 5 units (yellow badge)
Action: Stock In → Add 50 units
New Stock: 55 units (green badge)
Status: Low Stock → In Stock
```

### Example 3: Maintenance Request Uses Parts
```
Maintenance Request #45
Parts Used:
- Brake Pad (BP-FRONT-001) × 2 sets
- Oil Filter (OF-STD-001) × 1 unit

Before Maintenance:
- Brake Pad stock: 20 units
- Oil Filter stock: 55 units

After Completion:
- Brake Pad stock: 18 units
- Oil Filter stock: 54 units
```

## Best Practices

### 1. **Part Creation**
- Use descriptive names
- Follow SKU naming convention (e.g., CAT-TYPE-NUM)
- Always upload clear, high-quality pictures
- Include detailed descriptions
- Set realistic initial quantities

### 2. **SKU Format Suggestions**
```
BP-FRONT-001    (Brake Pad - Front - 001)
OF-STD-001      (Oil Filter - Standard - 001)
TR-ALL-001      (Tire - All Season - 001)
BT-12V-001      (Battery - 12V - 001)
WP-ELC-001      (Wiper - Electric - 001)
```

### 3. **Stock Management**
- Regular stock audits
- Reorder when quantity ≤ 10
- Maintain minimum 5-10 units buffer
- Update cost when prices change
- Monitor low stock alerts

### 4. **Picture Guidelines**
- Square images work best (500x500px minimum)
- Clear product photos
- White or neutral background
- Show actual part when possible
- JPG or PNG format
- Maximum 2MB file size

### 5. **Branch Organization**
- Assign parts to correct branch
- Transfer parts via Stock In if needed
- Keep branch inventory updated
- Coordinate between branches

## Reporting & Analytics

### Available Data
- Total parts count
- Stock levels by branch
- Low stock items
- Out of stock items
- Parts usage history
- Cost analysis
- Maintenance parts consumption

### Query Examples

**Find Low Stock Items:**
```php
$lowStock = PartStock::where('quantity', '<=', 10)
    ->where('branch_id', $branchId)
    ->with('part')
    ->get();
```

**Calculate Total Inventory Value:**
```php
$totalValue = Part::where('branch_id', $branchId)
    ->get()
    ->sum(function($part) {
        $stock = $part->stock()->where('branch_id', $branchId)->first();
        $quantity = $stock ? $stock->quantity : 0;
        return $part->cost * $quantity;
    });
```

**Parts Used This Month:**
```php
$partsUsed = MaintenanceRequestPart::whereHas('maintenanceRequest', function($q) {
    $q->where('status', 'completed')
      ->whereMonth('completed_at', now()->month);
})->sum('quantity');
```

## Troubleshooting

### Issue: Picture not displaying
**Solutions:**
1. Check storage link: `php artisan storage:link`
2. Verify file exists in `storage/app/public/parts/`
3. Check file permissions
4. Clear browser cache

### Issue: Stock not updating
**Solutions:**
1. Verify branch_id matches
2. Check part_stock table for record
3. Ensure Stock In permissions
4. Review database constraints

### Issue: Duplicate SKU error
**Solutions:**
1. SKUs must be unique across system
2. Use different SKU format
3. Check existing parts list
4. Add branch prefix if needed

## API Reference (Future Enhancement)

### Potential Endpoints
```
GET    /api/parts              - List all parts
GET    /api/parts/{id}         - Get part details
POST   /api/parts              - Create new part
PUT    /api/parts/{id}         - Update part
DELETE /api/parts/{id}         - Delete part
POST   /api/parts/{id}/stock   - Add stock
GET    /api/parts/low-stock    - Get low stock items
```

## Maintenance & Updates

### Regular Tasks
- Weekly stock audits
- Monthly inventory reconciliation
- Quarterly price updates
- Annual stock cleanup
- Picture quality review

### Database Maintenance
```bash
# Optimize parts table
php artisan db:optimize

# Clear old part pictures
php artisan parts:cleanup-images

# Generate inventory report
php artisan parts:generate-report
```

## Security Considerations

### File Upload Security
- File type validation (images only)
- File size limit (2MB)
- Unique file naming
- Secure storage location
- No executable files allowed

### Data Access
- Role-based permissions
- Branch isolation
- Audit logging recommended
- Input sanitization
- CSRF protection

## Future Enhancements

### Planned Features
1. Part categories/types
2. Supplier management
3. Purchase orders
4. Barcode scanning
5. Stock transfer between branches
6. Automated reorder alerts
7. Bulk import/export
8. Parts warranty tracking
9. Return/exchange management
10. Mobile app integration

---

**Last Updated:** October 9, 2025  
**System Version:** eRide Transport Management v1.0  
**Module:** Parts & Inventory Management
