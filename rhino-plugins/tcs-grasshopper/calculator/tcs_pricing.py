"""
TCS Pricing Calculator - Grasshopper Component
GHPython component for cabinet pricing calculations with override support.

INPUTS:
    width: float - Cabinet width in inches
    height: float - Cabinet height in inches
    depth: float - Cabinet depth in inches
    cabinet_type: str - Cabinet type (base, wall, tall)
    complexity_score: float - Complexity score (0-10)
    price_per_lf_override: float - Override price per LF (0 to use default)
    material_multiplier: float - Material cost multiplier (default 1.0)
    api_pricing_json: str - Pricing JSON from API (optional)

OUTPUTS:
    linear_feet: float - Linear feet
    base_price_lf: float - Base price per linear foot
    effective_price_lf: float - Price per LF after adjustments
    subtotal: float - Price before complexity adjustment
    complexity_adjustment: float - Price adjustment for complexity
    material_adjustment: float - Price adjustment for materials
    total_price: float - Final calculated price
    pricing_breakdown: str - Detailed pricing breakdown text
    pricing_json: str - Full pricing as JSON

USAGE IN GRASSHOPPER:
1. Connect dimensions from Cabinet Calculator or sliders
2. Set cabinet_type for base pricing tier
3. Optionally set complexity_score and multipliers
4. Use total_price for display or further calculations

TCS PRICING TIERS (Default):
    Base: $125/LF
    Wall: $95/LF
    Tall: $150/LF
    Custom: Based on complexity
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Pricing Calculator"
ghenv.Component.NickName = "TCS Price"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Calculator"

# TCS Default Pricing ($ per linear foot)
DEFAULT_PRICING = {
    'base': 125.0,
    'wall': 95.0,
    'upper': 95.0,
    'tall': 150.0,
    'pantry': 150.0,
    'vanity': 110.0,
    'drawer_base': 140.0,
    'sink_base': 135.0,
    'corner': 175.0,
    'custom': 150.0,
}

# Complexity adjustment thresholds
COMPLEXITY_THRESHOLDS = [
    (0, 3, 0.0),      # Simple: no adjustment
    (3, 5, 0.10),     # Moderate: +10%
    (5, 7, 0.20),     # Complex: +20%
    (7, 9, 0.35),     # Very Complex: +35%
    (9, 10, 0.50),    # Extreme: +50%
]

# Material multipliers
MATERIAL_MULTIPLIERS = {
    'standard': 1.0,
    'premium': 1.25,
    'exotic': 1.50,
    'custom': 1.75,
}


def get_base_price(cabinet_type):
    """Get base price per linear foot for cabinet type."""
    return DEFAULT_PRICING.get(
        cabinet_type.lower() if cabinet_type else 'base',
        DEFAULT_PRICING['base']
    )


def calculate_complexity_adjustment(base_price, complexity_score):
    """
    Calculate price adjustment based on complexity score.
    Returns (adjustment_percentage, adjustment_amount).
    """
    if complexity_score is None or complexity_score <= 0:
        return 0.0, 0.0

    # Find matching threshold
    for min_score, max_score, adjustment in COMPLEXITY_THRESHOLDS:
        if min_score <= complexity_score < max_score:
            return adjustment, base_price * adjustment

    # Above max threshold
    return 0.50, base_price * 0.50


def calculate_material_adjustment(base_price, multiplier):
    """
    Calculate material cost adjustment.
    Returns adjustment amount (can be positive or negative).
    """
    if multiplier is None or multiplier == 1.0:
        return 0.0

    return base_price * (multiplier - 1.0)


def format_currency(amount):
    """Format amount as currency string."""
    return "${:,.2f}".format(amount)


def format_breakdown(lf, base_price, effective_price, subtotal,
                     complexity_adj, material_adj, total, cabinet_type):
    """Format pricing breakdown as text."""
    lines = [
        "TCS PRICING BREAKDOWN",
        "=" * 40,
        "",
        "Cabinet Type: {}".format(cabinet_type.title() if cabinet_type else "Base"),
        "Linear Feet: {:.2f}'".format(lf),
        "",
        "BASE CALCULATION:",
        "  Base Price/LF: {}".format(format_currency(base_price)),
        "  Effective Price/LF: {}".format(format_currency(effective_price)),
        "  Subtotal ({:.2f}' x {}): {}".format(
            lf, format_currency(effective_price), format_currency(subtotal)
        ),
        "",
        "ADJUSTMENTS:",
        "  Complexity: {} ({:+.0%})".format(
            format_currency(complexity_adj),
            complexity_adj / subtotal if subtotal else 0
        ),
        "  Material: {} ({:+.0%})".format(
            format_currency(material_adj),
            material_adj / subtotal if subtotal else 0
        ),
        "",
        "-" * 40,
        "TOTAL: {}".format(format_currency(total)),
        "-" * 40,
    ]

    return "\n".join(lines)


def parse_api_pricing(json_str):
    """Parse pricing from API response."""
    if not json_str:
        return None

    try:
        data = json.loads(json_str) if isinstance(json_str, str) else json_str
        return data.get('pricing', data)
    except:
        return None


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
linear_feet = 0.0
base_price_lf = 0.0
effective_price_lf = 0.0
subtotal = 0.0
complexity_adjustment = 0.0
material_adjustment = 0.0
total_price = 0.0
pricing_breakdown = ""
pricing_json = "{}"

# Calculate if we have width
if width and width > 0:
    # Calculate linear feet
    linear_feet = float(width) / 12.0

    # Get base price for cabinet type
    cab_type = cabinet_type if cabinet_type else 'base'
    base_price_lf = get_base_price(cab_type)

    # Apply override if provided
    if price_per_lf_override and price_per_lf_override > 0:
        effective_price_lf = float(price_per_lf_override)
    else:
        effective_price_lf = base_price_lf

    # Calculate subtotal
    subtotal = linear_feet * effective_price_lf

    # Calculate complexity adjustment
    _, complexity_adjustment = calculate_complexity_adjustment(
        subtotal,
        complexity_score if complexity_score else 0
    )

    # Calculate material adjustment
    mat_mult = material_multiplier if material_multiplier else 1.0
    material_adjustment = calculate_material_adjustment(subtotal, mat_mult)

    # Check for API pricing override
    api_pricing = parse_api_pricing(api_pricing_json)
    if api_pricing:
        # Use API values if available
        if 'total_price' in api_pricing:
            total_price = float(api_pricing['total_price'])
        else:
            total_price = subtotal + complexity_adjustment + material_adjustment

        if 'adjustment_amount' in api_pricing:
            complexity_adjustment = float(api_pricing['adjustment_amount'])
    else:
        # Calculate total
        total_price = subtotal + complexity_adjustment + material_adjustment

    # Format breakdown
    pricing_breakdown = format_breakdown(
        linear_feet,
        base_price_lf,
        effective_price_lf,
        subtotal,
        complexity_adjustment,
        material_adjustment,
        total_price,
        cab_type
    )

    # Build JSON output
    pricing_data = {
        'cabinet_type': cab_type,
        'linear_feet': linear_feet,
        'base_price_per_lf': base_price_lf,
        'effective_price_per_lf': effective_price_lf,
        'subtotal': subtotal,
        'complexity_score': complexity_score if complexity_score else 0,
        'complexity_adjustment': complexity_adjustment,
        'material_multiplier': mat_mult,
        'material_adjustment': material_adjustment,
        'total_price': total_price,
        'overridden': bool(price_per_lf_override and price_per_lf_override > 0),
    }
    pricing_json = json.dumps(pricing_data, indent=2)

else:
    pricing_breakdown = "Enter width to calculate pricing"


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_price_summary():
    """Get one-line price summary."""
    if linear_feet > 0:
        return "{:.2f}' x {}/LF = {}".format(
            linear_feet,
            format_currency(effective_price_lf),
            format_currency(total_price)
        )
    return "No price calculated"


def estimate_run_price(cabinet_widths, cab_type='base'):
    """
    Estimate total price for a run of cabinets.
    cabinet_widths: list of widths in inches
    """
    total_lf = sum(w / 12.0 for w in cabinet_widths)
    base_price = get_base_price(cab_type)
    return total_lf * base_price


def get_pricing_tier_table():
    """Get pricing tier table for display."""
    lines = ["PRICING TIERS ($/LF):", "-" * 30]
    for cab_type, price in sorted(DEFAULT_PRICING.items()):
        lines.append("  {:<15} {}".format(
            cab_type.title(),
            format_currency(price)
        ))
    return "\n".join(lines)


# Print component info
print("")
print("TCS Pricing Calculator")
print("=" * 40)
if linear_feet > 0:
    print(get_price_summary())
    print("")
    print("Adjustments:")
    print("  Complexity: {}".format(format_currency(complexity_adjustment)))
    print("  Material: {}".format(format_currency(material_adjustment)))
    print("")
    print("TOTAL: {}".format(format_currency(total_price)))
else:
    print("Enter dimensions to calculate pricing")
