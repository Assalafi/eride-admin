# Stock Quantity Architecture - Complete Documentation

## Overview
The inventory system uses a **separate `part_stock` table** for quantity tracking, NOT the `parts` table. This enables multi-branch inventory management.

## Database Architecture

### Tables

#### 1. **parts** table (NO quantity column)
```sql
CREATE TABLE parts (
    id BIGINT PRIMARY KEY,
    branch_id BIGINT,              -- Part's "home" branch
    name VARCHAR(255),
    sku VARCHAR(255) UNIQUE,
    picture VARCHAR(255),          -- Path to image
    description TEXT,
    cost DECIMAL(10,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);
```

#### 2. **part_stock** table (HAS quantity column)
```sql
CREATE TABLE part_stock (
    id BIGINT PRIMARY KEY,
    part_id BIGINT,                -- Which part
    branch_id BIGINT,              -- Which branch's stock
    quantity INT DEFAULT 0,        -- ← QUANTITY STORED HERE
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    UNIQUE (part_id, branch_id)    -- One stock record per part per branch
);
```

## Relationships

### Part Model
```php
class Part extends Model
{
    // One part can have stock in multiple branches
    public function stock(): HasMany
    {
        return $this->hasMany(PartStock::class);
    }
    
    // Helper method to get quantity for specific branch
    public function getStockForBranch($branchId): int
    {
        $stock = $this->stock()->where('branch_id', $branchId)->first();
        return $stock ? $stock->quantity : 0;
    }
    
    // Get total stock across all branches
    public function getTotalStock(): int
    {
        return $this->stock()->sum('quantity');
    }
}
```

### PartStock Model
```php
class PartStock extends Model
{
    protected $table = 'part_stock';
    
    protected $fillable = [
        'part_id',
        'branch_id',
        'quantity',
    ];
    
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
    
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
```

## Data Flow

### Example: Brake Pad across 3 branches

```
parts table:
┌────┬────────────┬──────────────┬───────┬──────┐
│ id │ branch_id  │ name         │ sku   │ cost │
├────┼────────────┼──────────────┼───────┼──────┤
│ 1  │ 1 (Main)   │ Brake Pad    │ BP-001│ 15000│
└────┴────────────┴──────────────┴───────┴──────┘

part_stock table:
┌────┬─────────┬────────────┬──────────┐
│ id │ part_id │ branch_id  │ quantity │
├────┼─────────┼────────────┼──────────┤
│ 1  │ 1       │ 1 (Main)   │ 20       │ ← Main Branch: 20 units
│ 2  │ 1       │ 2 (North)  │ 15       │ ← North Branch: 15 units
│ 3  │ 1       │ 3 (South)  │ 5        │ ← South Branch: 5 units
└────┴─────────┴────────────┴──────────┘

When Main Branch user views this part:
→ Shows: 20 units (from row 1)

When North Branch user views this part:
→ Shows: 15 units (from row 2)

When South Branch user views this part:
→ Shows: 5 units (from row 3)
```

## Controller Implementation

### Index Method (List Parts)
```php
public function index()
{
    $user = auth()->user();
    
    // Eager load stock relationship (filtered by branch if needed)
    $parts = Part::with(['branch', 'stock' => function ($query) use ($user) {
        if (!$user->hasRole('Super Admin')) {
            // Non-admin users: load only their branch's stock
            $query->where('branch_id', $user->branch_id);
        }
        // Super Admin: load all branch stocks
    }])->paginate(20);
    
    // Calculate statistics
    $totalParts = Part::count();
    
    $stockQuery = PartStock::query();
    if (!$user->hasRole('Super Admin')) {
        $stockQuery->where('branch_id', $user->branch_id);
    }
    
    $inStockParts = (clone $stockQuery)->where('quantity', '>', 10)->count();
    $lowStockParts = (clone $stockQuery)->whereBetween('quantity', [1, 10])->count();
    $outOfStockParts = Part::whereDoesntHave('stock', function ($query) use ($user) {
        if (!$user->hasRole('Super Admin')) {
            $query->where('branch_id', $user->branch_id)->where('quantity', '>', 0);
        } else {
            $query->where('quantity', '>', 0);
        }
    })->count();
    
    return view('admin.parts.index', compact('parts', 'totalParts', 'inStockParts', 'lowStockParts', 'outOfStockParts'));
}
```

### Store Method (Create Part)
```php
public function store(Request $request)
{
    // Create part (NO quantity column)
    $part = Part::create([
        'branch_id' => $request->branch_id,
        'name' => $request->name,
        'sku' => strtoupper($request->sku),
        'picture' => $picturePath,
        'description' => $request->description,
        'cost' => $request->cost,
    ]);
    
    // Create initial stock for the branch (IN part_stock table)
    if ($request->initial_quantity > 0) {
        PartStock::create([
            'part_id' => $part->id,
            'branch_id' => $request->branch_id,
            'quantity' => $request->initial_quantity,  // ← Quantity here
        ]);
    }
}
```

### Stock In Method (Add Stock)
```php
public function stockIn(Request $request, Part $part)
{
    $branchId = auth()->user()->branch_id;
    
    // Find or create stock record for this part + branch
    $stock = PartStock::firstOrCreate(
        ['part_id' => $part->id, 'branch_id' => $branchId],
        ['quantity' => 0]
    );
    
    // Increment quantity in part_stock table
    $stock->increment('quantity', $request->quantity);
}
```

## Blade View Implementation

### Index View
```blade
@forelse($parts as $part)
    @php
        // Get current user's branch ID
        $userBranchId = auth()->user()->branch_id;
        
        // Filter eager-loaded stock collection for current branch
        $stock = $part->stock->where('branch_id', $userBranchId)->first();
        
        // Get quantity from part_stock table (or 0 if no record)
        $quantity = $stock ? $stock->quantity : 0;
    @endphp
    
    <tr>
        <td>{{ $part->name }}</td>
        <td>
            <!-- Display quantity badge -->
            <span class="badge {{ $quantity > 10 ? 'bg-success' : ($quantity > 0 ? 'bg-warning' : 'bg-danger') }}">
                {{ $quantity }} units
            </span>
        </td>
        <td>
            <!-- Status badge -->
            @if($quantity > 10)
                <span class="badge bg-success">In Stock</span>
            @elseif($quantity > 0)
                <span class="badge bg-warning">Low Stock</span>
            @else
                <span class="badge bg-danger">Out of Stock</span>
            @endif
        </td>
        <td>
            <!-- Stock In button -->
            <button data-bs-toggle="modal" data-bs-target="#stockInModal{{ $part->id }}">
                Stock In
            </button>
        </td>
    </tr>
    
    <!-- Stock In Modal -->
    <div class="modal" id="stockInModal{{ $part->id }}">
        <form action="{{ route('admin.parts.stock-in', $part) }}" method="POST">
            @csrf
            <p>Current Stock: <strong>{{ $quantity }} units</strong></p>
            <input type="number" name="quantity" min="1" required>
            <button type="submit">Add to Stock</button>
        </form>
    </div>
@endforelse
```

## Query Examples

### Get part stock for specific branch
```php
// Method 1: Using relationship (causes query)
$quantity = $part->getStockForBranch($branchId);

// Method 2: Using eager-loaded collection (no query)
$stock = $part->stock->where('branch_id', $branchId)->first();
$quantity = $stock ? $stock->quantity : 0;
```

### Get total stock across all branches
```php
$totalStock = $part->getTotalStock();
// or
$totalStock = PartStock::where('part_id', $part->id)->sum('quantity');
```

### Get low stock items for a branch
```php
$lowStockParts = PartStock::where('branch_id', $branchId)
    ->whereBetween('quantity', [1, 10])
    ->with('part')
    ->get();
```

### Get parts with no stock in a branch
```php
$noStockParts = Part::whereDoesntHave('stock', function($query) use ($branchId) {
    $query->where('branch_id', $branchId);
})->get();
```

## Maintenance Integration

### When Maintenance is Completed
```php
foreach ($maintenanceRequest->parts as $part) {
    $quantity = $part->pivot->quantity;
    
    // Find stock record for requesting branch
    $stock = PartStock::where([
        'part_id' => $part->id,
        'branch_id' => $maintenanceRequest->driver->branch_id
    ])->first();
    
    // Deduct from branch stock (in part_stock table)
    if ($stock && $stock->quantity >= $quantity) {
        $stock->decrement('quantity', $quantity);
    } else {
        throw new Exception('Insufficient stock');
    }
}
```

## Benefits of This Architecture

### 1. **Multi-Branch Support**
- Each branch has independent stock tracking
- No cross-branch inventory confusion
- Branch managers see only their stock

### 2. **Scalability**
- Add unlimited branches without schema changes
- Stock records created on-demand
- No wasted storage for zero-stock branches

### 3. **Flexibility**
- Transfer stock between branches (future feature)
- Branch-specific reorder points
- Branch-specific stock reports

### 4. **Data Integrity**
- UNIQUE constraint prevents duplicate records
- CASCADE delete cleans up stock when part deleted
- Foreign keys ensure referential integrity

### 5. **Performance**
- Eager loading prevents N+1 queries
- Indexed foreign keys for fast lookups
- Efficient filtering by branch

## Common Patterns

### Pattern 1: Display Current Branch Stock
```php
// Controller
$parts = Part::with(['stock' => function($query) use ($user) {
    $query->where('branch_id', $user->branch_id);
}])->get();

// Blade
$quantity = $part->stock->first()?->quantity ?? 0;
```

### Pattern 2: Display All Branch Stocks (Super Admin)
```php
// Controller
$parts = Part::with('stock.branch')->get();

// Blade
@foreach($part->stock as $stockRecord)
    {{ $stockRecord->branch->name }}: {{ $stockRecord->quantity }} units
@endforeach
```

### Pattern 3: Check Stock Before Dispensing
```php
$stock = PartStock::where([
    'part_id' => $partId,
    'branch_id' => $branchId
])->first();

if (!$stock || $stock->quantity < $requiredQuantity) {
    throw new Exception('Insufficient stock');
}

$stock->decrement('quantity', $requiredQuantity);
```

## Troubleshooting

### Issue: Quantity not showing
**Check:**
1. Does part_stock record exist for this part + branch?
2. Is eager loading including stock relationship?
3. Is branch_id filter correct in blade?

### Issue: Wrong quantity displayed
**Check:**
1. Which branch's stock are you viewing?
2. Is the blade filtering by correct branch_id?
3. Are multiple stock records for same part+branch (should be impossible)?

### Issue: Stock In not working
**Check:**
1. Is PartStock model imported in controller?
2. Does user have correct branch_id?
3. Is firstOrCreate working correctly?

## Summary

**✅ Storage Location:** `part_stock` table (NOT `parts` table)  
**✅ Key Column:** `part_stock.quantity`  
**✅ Relationship:** `Part hasMany PartStock`  
**✅ Access Pattern:** Eager load + collection filter  
**✅ Architecture:** Multi-branch, scalable, flexible  

This design enables:
- Independent branch inventories
- Accurate stock tracking
- Scalable growth
- Clean separation of concerns
- Performance optimization through eager loading

---

**Document Version:** 1.0  
**Last Updated:** October 9, 2025  
**System:** eRide Transport Management
