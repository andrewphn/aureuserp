<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Webkul\TcsCms\Models\Faq;
use Webkul\TcsCms\Models\HomeSection;
use Webkul\TcsCms\Models\Journal;
use Webkul\TcsCms\Models\Material;
use Webkul\TcsCms\Models\PortfolioProject;
use Webkul\TcsCms\Models\Service;

class TcsContentSeeder extends Seeder
{
    /**
     * Seed all TCS CMS content from the original TCS Website.
     */
    public function run(): void
    {
        $this->command->info('Starting TCS Content Import...');

        $this->seedHomeSections();
        $this->seedPortfolioProjects();
        $this->seedServices();
        $this->seedMaterials();
        $this->seedFaqs();
        $this->seedJournals();

        $this->command->info('TCS Content Import completed successfully!');
    }

    /**
     * Seed Home Sections
     */
    protected function seedHomeSections(): void
    {
        $this->command->info('Seeding Home Sections...');

        // Clear existing sections
        HomeSection::query()->delete();

        // Hero Section
        HomeSection::create([
            'section_key' => 'hero',
            'section_type' => 'hero',
            'title' => 'Custom Cabinetry, Fine Millwork & Bespoke Furniture',
            'subtitle' => 'Exceptional craftsmanship for discerning clients',
            'background_image' => '/images/dining_table_web.mp4',
            'cta_text' => 'Request a Consultation',
            'cta_link' => '/contact',
            'position' => 1,
            'is_active' => true,
        ]);

        // Owner Note Section
        HomeSection::create([
            'section_key' => 'owner_note',
            'section_type' => 'owner_note',
            'title' => 'From the Master Craftsman',
            'settings' => [
                'background_color' => 'bg-neutral-900',
                'text_color' => 'text-white',
            ],
            'author_info' => [
                'name' => 'Bryan Patton',
                'title' => 'Lead Design Craftsman & Master Woodwright',
                'message' => "I want to sincerely thank all our customers who've trusted us with their projects over the years. Your support has allowed us to do what we love every day—working with our hands and bringing your ideas to life. It's been a privilege to be part of your homes and businesses.",
                'closing' => 'To our future clients: I look forward to sitting down with you, understanding your needs, and creating something that will serve you for years to come. Thank you for considering our workshop for your next project.',
            ],
            'position' => 2,
            'is_active' => true,
        ]);

        // Services Section
        HomeSection::create([
            'section_key' => 'services',
            'section_type' => 'services',
            'title' => 'Our Services',
            'subtitle' => 'Luxury Custom Woodworking for Distinctive Spaces',
            'content' => 'Heirloom-quality custom woodwork for distinctive residential and commercial interiors. We deliver finely crafted solutions using premium materials and proven techniques.',
            'settings' => [
                'background_color' => 'bg-neutral-50',
            ],
            'service_items' => [
                [
                    'sequence' => '01',
                    'title' => 'Residential Cabinetry',
                    'subtitle' => 'Kitchen & Bath Cabinetry',
                    'description' => 'We specialize in handcrafted cabinetry that blends timeless design with modern functionality. From full kitchen renovations to elegant bathroom vanities, our residential cabinetry features premium hardwoods, custom finishes, and thoughtful details tailored to your space.',
                    'features' => [
                        'Kitchen cabinetry and islands',
                        'Bathroom vanities and storage',
                        'Built-in closets and shelving',
                    ],
                    'imageUrl' => '/images/projects/kitchen-cabinetry.png',
                    'imageAlt' => 'Luxury kitchen cabinetry',
                    'imagePosition' => 'right',
                    'linkUrl' => '/services#residential',
                    'linkText' => 'Learn more about Residential Cabinetry',
                ],
                [
                    'sequence' => '02',
                    'title' => 'Custom Furniture',
                    'subtitle' => 'One-of-a-Kind Statement Pieces',
                    'description' => "Our custom furniture service brings your vision to life—from dining tables and media consoles to bedroom sets and accent pieces. Each piece is built to last and designed to harmonize with your home's character and your personal style.",
                    'features' => [
                        'Dining tables and chairs',
                        'Bed frames and bedroom furniture',
                        'Custom desks and shelving',
                    ],
                    'imageUrl' => '/images/custom_table_closeup.png',
                    'imageAlt' => 'Custom furniture',
                    'imagePosition' => 'left',
                    'linkUrl' => '/services#furniture',
                    'linkText' => 'Learn more about Custom Furniture',
                ],
                [
                    'sequence' => '03',
                    'title' => 'Commercial Millwork',
                    'subtitle' => 'Premium Solutions for Professional Spaces',
                    'description' => "From high-end retail fixtures and hospitality features to corporate reception areas and boardrooms, we provide custom millwork that reflects your brand's identity while ensuring functionality, durability, and beauty.",
                    'features' => [
                        'Reception desks and lobbies',
                        'Office and conference furniture',
                        'Retail displays and hospitality fixtures',
                    ],
                    'imageUrl' => '/images/Commercial.png',
                    'imageAlt' => 'Commercial millwork project',
                    'imagePosition' => 'right',
                    'linkUrl' => '/services#commercial',
                    'linkText' => 'Learn more about Commercial Millwork',
                ],
            ],
            'cta_text' => 'Explore Our Custom Woodworking Services',
            'cta_link' => '/services',
            'position' => 3,
            'is_active' => true,
        ]);

        // Process Section
        HomeSection::create([
            'section_key' => 'process',
            'section_type' => 'process',
            'title' => 'Our Journey',
            'subtitle' => 'FROM CONCEPT TO CREATION',
            'settings' => [
                'background_color' => 'bg-white',
            ],
            'process_steps' => [
                [
                    'number' => '01',
                    'title' => 'Discovery',
                    'description' => 'We begin with a comprehensive consultation to understand your vision, needs, and space requirements.',
                ],
                [
                    'number' => '02',
                    'title' => 'Design',
                    'description' => 'Our designers transform your ideas into detailed visual concepts, with revisions to ensure the design meets your expectations.',
                ],
                [
                    'number' => '03',
                    'title' => 'Sourcing',
                    'description' => 'We secure the highest-quality materials, selecting timber with perfect grain patterns and structural integrity for your project.',
                ],
                [
                    'number' => '04',
                    'title' => 'Production',
                    'description' => 'Skilled artisans transform raw materials into finished pieces using traditional techniques and modern precision tools.',
                ],
                [
                    'number' => '05',
                    'title' => 'Delivery',
                    'description' => 'We ensure your custom woodwork arrives safely in perfect condition, with care instructions for lasting beauty.',
                ],
            ],
            'cta_text' => 'Learn More About Our Process',
            'cta_link' => '/process',
            'position' => 4,
            'is_active' => true,
        ]);

        // Featured Projects Section
        HomeSection::create([
            'section_key' => 'projects',
            'section_type' => 'projects',
            'title' => 'Featured Work',
            'settings' => [
                'background_color' => 'bg-white',
            ],
            'cta_text' => 'Explore Our Portfolio',
            'cta_link' => '/work',
            'position' => 5,
            'is_active' => true,
        ]);

        // Testimonials Section
        HomeSection::create([
            'section_key' => 'testimonials',
            'section_type' => 'testimonials',
            'title' => 'Client Testimonials',
            'subtitle' => 'What Our Clients Say',
            'content' => "Don't just take our word for it. Hear from our satisfied clients about their experience working with TCS Fine Woodworking and the exceptional results delivered by Bryan and his team.",
            'settings' => [
                'background_color' => 'bg-white',
            ],
            'testimonial_items' => [
                [
                    'quote' => 'Perfection! TCS did a remarkable job on our custom built in. Within a matter of weeks, Bryan was able to design, build, and install our wall to wall cabinet/shelfing built in. The team was professional, communicative, and delivered exceptional quality.',
                    'author' => 'Kevin Guley',
                    'position' => 'Custom Built-in Project',
                ],
                [
                    'quote' => 'Bryan and the TCS team just finished building the most gorgeous set of mudroom cabinets for my home. We are thrilled with them! From start to finish Bryan and his team were extremely professional, very responsive, and completed my project so quickly.',
                    'author' => 'L R',
                    'position' => 'Custom Mudroom Cabinets',
                ],
                [
                    'quote' => 'Bryan and his team are incredible. Talented, professional, with work that is always remarkable in both its quality and value. We have used them for many jobs — custom cabinetry, custom banister woodwork, and more. Bryan is always extremely thorough, holding himself and his team to very high standards.',
                    'author' => 'Kimberly Noble',
                    'position' => 'Multiple Custom Projects',
                ],
            ],
            'cta_text' => 'Start Your Custom Project',
            'cta_link' => '/contact',
            'position' => 6,
            'is_active' => true,
        ]);

        // Journal Section
        HomeSection::create([
            'section_key' => 'journal',
            'section_type' => 'journal',
            'title' => 'Craftsmanship Insights',
            'settings' => [
                'background_color' => 'bg-neutral-50',
            ],
            'cta_text' => 'Explore All Craftsmanship Insights',
            'cta_link' => '/journal',
            'position' => 7,
            'is_active' => true,
        ]);

        $this->command->info('✓ Home Sections seeded: ' . HomeSection::count());
    }

    /**
     * Seed Portfolio Projects
     */
    protected function seedPortfolioProjects(): void
    {
        $this->command->info('Seeding Portfolio Projects...');

        $projects = [
            [
                'title' => 'Gathered View: A Modern Inset Kitchen Overlooking the Wallkill Valley',
                'client_name' => 'Smith Family',
                'summary' => 'Where craftsmanship meets connection — a warm, modern kitchen designed for community and quiet moments alike.',
                'description' => "Perched above the scenic Wallkill Valley, this kitchen transforms existing constraints into an opportunity for intentional, human-centered design. With breathtaking views on one side and an inviting island on the other, the space anchors the home's social core. Black walnut inset cabinetry brings warmth, while matte black painted doors and drawers add a clean, modern counterpoint. Despite space constraints and a fixed appliance layout, the kitchen delivers high-function storage through tailored pullouts, custom millwork, and precision-built pantry solutions. This is a kitchen designed not just for cooking — but for gathering, gazing, and grounding. Features include inset cabinetry in solid black walnut, matte black painted doors and drawers, custom pullouts (tray storage, dual trash/recycling, spice cabinets, and full-height pantry units), exterior-vented range hood integrated within existing wall constraints, and a large seating island designed to foster connection while opening to valley views.",
                'category' => 'cabinetry',
                'materials' => ['American Black Walnut', 'Matte Black Paint', 'Quartz Countertops'],
                'techniques' => ['Inset Cabinetry', 'Custom Pullouts', 'Hand-Finished Surfaces', 'Integrated Hardware'],
                'dimensions' => ['Kitchen: 13\' x 10\' (wall to wall)', 'Island: 8\' x 3\'', 'Base Cabinet Height: 34.5"', 'Countertop Height: 36"', 'Upper Cabinet Height: 36"', 'Tall Cabinet Depth: 24"', 'Minimum Aisle Clearance: 42" (between island & range)'],
                'timeline' => '8 weeks',
                'featured' => true,
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Gathered View: A Modern Inset Kitchen | TCS Woodworking',
                'meta_description' => 'Modern black walnut inset kitchen overlooking the Wallkill Valley, designed for gathering with custom pullouts, matte black accents, and panoramic views.',
                'portfolio_order' => 1,
            ],
            [
                'title' => 'Custom Dining Table',
                'client_name' => 'Johnson Residence',
                'summary' => 'A handcrafted dining table that seats 10 with custom chairs.',
                'description' => "This custom dining table project showcases our commitment to quality craftsmanship. The table features a solid walnut top with butterfly joints for stability, supported by a steel base. We also created matching chairs with ergonomic design and comfortable upholstery. The project demonstrates our ability to combine traditional woodworking techniques with modern design elements.",
                'category' => 'furniture',
                'materials' => ['Black Walnut', 'Steel', 'Leather'],
                'techniques' => ['Butterfly Joints', 'Hand Planing', 'Custom Upholstery'],
                'dimensions' => ['Table: 96" x 42"', 'Height: 30"'],
                'timeline' => '6 weeks',
                'featured' => true,
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Custom Dining Table | TCS Woodworking',
                'meta_description' => 'Discover our custom dining table project featuring handcrafted walnut and steel construction.',
                'portfolio_order' => 2,
            ],
            [
                'title' => 'Commercial Office Millwork',
                'client_name' => 'TechStart Inc.',
                'summary' => 'Custom millwork for a modern tech office space.',
                'description' => "This commercial project involved creating custom millwork for a tech company's headquarters. We designed and built reception desks, conference room tables, and custom storage solutions. The project required precise attention to detail and coordination with the building's architects and contractors.",
                'category' => 'millwork',
                'materials' => ['Maple', 'Birch Plywood', 'Laminate'],
                'techniques' => ['CNC Machining', 'Edge Banding', 'Custom Hardware'],
                'dimensions' => ['Various'],
                'timeline' => '12 weeks',
                'featured' => false,
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Commercial Office Millwork | TCS Woodworking',
                'meta_description' => 'View our commercial millwork project for a modern tech office space.',
                'portfolio_order' => 3,
            ],
        ];

        foreach ($projects as $projectData) {
            $projectData['slug'] = Str::slug($projectData['title']);
            PortfolioProject::updateOrCreate(
                ['slug' => $projectData['slug']],
                $projectData
            );
        }

        $this->command->info('✓ Portfolio Projects seeded: ' . PortfolioProject::count());
    }

    /**
     * Seed Services
     */
    protected function seedServices(): void
    {
        $this->command->info('Seeding Services...');

        $services = [
            [
                'title' => 'Custom Furniture Design',
                'summary' => 'Handcrafted furniture pieces tailored to your space and style.',
                'description' => 'Our custom furniture design service brings your vision to life. We work closely with you to create unique pieces that perfectly fit your space and reflect your personal style. From concept to completion, we ensure every detail meets our high standards of craftsmanship.',
                'category' => 'furniture',
                'features' => [
                    ['title' => 'Custom Design Consultation', 'description' => 'In-depth consultation to understand your needs and preferences'],
                    ['title' => 'Material Selection', 'description' => 'Expert guidance in choosing the perfect wood species and finishes'],
                    ['title' => 'Handcrafted Construction', 'description' => 'Traditional woodworking techniques combined with modern precision'],
                    ['title' => 'Delivery & Installation', 'description' => 'Professional delivery and setup of your custom pieces'],
                ],
                'price_range' => '$2,500 - $15,000',
                'timeline' => '4-8 weeks',
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Custom Furniture Design | TCS Woodworking',
                'meta_description' => 'Discover our custom furniture design service, where craftsmanship meets personal style.',
                'position' => 1,
            ],
            [
                'title' => 'Custom Cabinetry',
                'summary' => 'Tailored storage solutions for kitchens, bathrooms, and beyond.',
                'description' => "Our custom cabinetry service provides perfectly fitted storage solutions for any space. We specialize in kitchen and bathroom cabinetry, built-in storage, and custom closet systems. Every piece is designed to maximize space and enhance your home's functionality.",
                'category' => 'cabinetry',
                'features' => [
                    ['title' => 'Space Planning', 'description' => 'Detailed measurements and 3D design visualization'],
                    ['title' => 'Custom Features', 'description' => 'Soft-close hinges, pull-out shelves, and custom organizers'],
                    ['title' => 'Material Options', 'description' => 'Wide selection of wood species, finishes, and hardware'],
                    ['title' => 'Professional Installation', 'description' => 'Expert installation and final adjustments'],
                ],
                'price_range' => '$5,000 - $30,000',
                'timeline' => '6-12 weeks',
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Custom Cabinetry | TCS Woodworking',
                'meta_description' => 'Explore our custom cabinetry services for kitchens, bathrooms, and storage solutions.',
                'position' => 2,
            ],
            [
                'title' => 'Commercial Millwork',
                'summary' => 'Custom millwork solutions for commercial and retail spaces.',
                'description' => 'Our commercial millwork service delivers high-quality, custom woodwork for businesses. We create reception desks, conference room tables, retail displays, and more. Our team works closely with architects and contractors to ensure perfect integration with your space.',
                'category' => 'millwork',
                'features' => [
                    ['title' => 'Project Coordination', 'description' => 'Seamless collaboration with architects and contractors'],
                    ['title' => 'Commercial-Grade Materials', 'description' => 'Durable materials suitable for high-traffic areas'],
                    ['title' => 'Custom Finishes', 'description' => 'Professional finishes that withstand daily use'],
                    ['title' => 'Installation & Support', 'description' => 'Efficient installation and ongoing maintenance support'],
                ],
                'price_range' => '$10,000 - $50,000',
                'timeline' => '8-16 weeks',
                'is_published' => true,
                'status' => 'published',
                'meta_title' => 'Commercial Millwork | TCS Woodworking',
                'meta_description' => 'Professional commercial millwork services for businesses and retail spaces.',
                'position' => 3,
            ],
        ];

        foreach ($services as $serviceData) {
            $serviceData['slug'] = Str::slug($serviceData['title']);
            Service::updateOrCreate(
                ['slug' => $serviceData['slug']],
                $serviceData
            );
        }

        $this->command->info('✓ Services seeded: ' . Service::count());
    }

    /**
     * Seed Materials
     */
    protected function seedMaterials(): void
    {
        $this->command->info('Seeding Materials...');

        $materials = [
            [
                'name' => 'Walnut',
                'scientific_name' => 'Juglans nigra',
                'description' => 'Rich, dark hardwood prized for its deep chocolate color and striking grain patterns.',
                'content' => 'Walnut is a premium hardwood known for its exceptional beauty and workability. Its heartwood ranges from rich chocolate brown to deep purple-black, while the sapwood is typically creamy white. The wood features straight to wavy grain with occasional burls and figure. This species is particularly valued for high-end cabinetry and furniture due to its stability and natural beauty that improves with age.',
                'featured_image' => 'images/materials/walnut.jpg',
                'gallery' => ['images/materials/walnut-1.jpg', 'images/materials/walnut-2.jpg', 'images/materials/walnut-3.jpg'],
                'properties' => [
                    'Hardness' => 'Very Hard (1010 lbf)',
                    'Density' => '0.55 g/cm³',
                    'Stability' => 'Good',
                    'Workability' => 'Excellent',
                    'Grain' => 'Straight to wavy',
                    'Finish' => 'Takes stain and finish exceptionally well',
                ],
                'sustainability' => 'Walnut is sustainably harvested in North America. The species is abundant and well-managed with selective harvesting practices ensuring forest regeneration.',
                'applications' => ['Fine Furniture', 'Kitchen & Bath Cabinetry', 'Decorative Veneers', 'Executive Office Furniture', 'Architectural Millwork', 'Custom Built-ins'],
                'position' => 1,
                'featured' => true,
                'is_published' => true,
            ],
            [
                'name' => 'Maple',
                'scientific_name' => 'Acer saccharum',
                'description' => 'Light, hard maple with exceptional durability and a clean, modern aesthetic.',
                'content' => 'Hard maple is one of the most versatile and durable hardwoods available. Its fine, uniform texture and light color make it perfect for modern designs. The wood can range from nearly white to light reddish-brown, with occasional mineral streaks adding character. Maple is particularly prized for painted finishes due to its smooth grain structure.',
                'featured_image' => 'images/materials/maple.jpg',
                'gallery' => ['images/materials/maple-1.jpg', 'images/materials/maple-2.jpg', 'images/materials/maple-3.jpg'],
                'properties' => [
                    'Hardness' => 'Very Hard (1450 lbf)',
                    'Density' => '0.63 g/cm³',
                    'Stability' => 'Excellent',
                    'Workability' => 'Good to Excellent',
                    'Grain' => 'Fine, uniform texture',
                    'Finish' => 'Excellent for paint and clear finishes',
                ],
                'sustainability' => 'Maple is abundant in North American forests and sustainably managed through responsible forestry practices.',
                'applications' => ['Kitchen Cabinets', 'Contemporary Furniture', 'Commercial Millwork', 'Painted Cabinetry', 'Retail Fixtures', 'Reception Desks'],
                'position' => 2,
                'featured' => true,
                'is_published' => true,
            ],
            [
                'name' => 'Cherry',
                'scientific_name' => 'Prunus serotina',
                'description' => 'Warm, rich hardwood that develops a beautiful patina over time.',
                'content' => 'Cherry is prized for its warm, reddish-brown color that deepens with age and exposure to light. The wood has a fine, straight grain with occasional figure and mineral streaks. Its smooth texture and natural luster make it perfect for fine furniture and cabinetry. Cherry is known for its consistent aging process, developing a rich amber patina that enhances its natural beauty.',
                'featured_image' => 'images/materials/cherry.jpg',
                'gallery' => ['images/materials/cherry-1.jpg', 'images/materials/cherry-2.jpg', 'images/materials/cherry-3.jpg'],
                'properties' => [
                    'Hardness' => 'Medium (950 lbf)',
                    'Density' => '0.50 g/cm³',
                    'Stability' => 'Good',
                    'Workability' => 'Excellent',
                    'Grain' => 'Fine, straight with occasional figure',
                    'Finish' => 'Natural oils enhance grain pattern',
                ],
                'sustainability' => 'Cherry is sustainably harvested in North America with proper forest management practices ensuring long-term availability.',
                'applications' => ['Fine Furniture', 'Traditional Cabinetry', 'Interior Trim & Molding', 'Boardroom Tables', 'Custom Built-ins', 'Decorative Accents'],
                'position' => 3,
                'featured' => true,
                'is_published' => true,
            ],
            [
                'name' => 'White Oak',
                'scientific_name' => 'Quercus alba',
                'description' => 'Durable hardwood with distinctive grain patterns and exceptional strength.',
                'content' => 'White Oak is renowned for its strength, durability, and distinctive grain pattern featuring prominent medullary rays. The heartwood is light to medium brown, often with an olive cast, while the sapwood is nearly white. This species offers excellent dimensional stability and natural water resistance, making it ideal for both interior and exterior applications.',
                'featured_image' => 'images/materials/white-oak.jpg',
                'gallery' => ['images/materials/white-oak-1.jpg', 'images/materials/white-oak-2.jpg', 'images/materials/white-oak-3.jpg'],
                'properties' => [
                    'Hardness' => 'Hard (1360 lbf)',
                    'Density' => '0.68 g/cm³',
                    'Stability' => 'Excellent',
                    'Workability' => 'Good',
                    'Grain' => 'Straight with prominent ray patterns',
                    'Finish' => 'Excellent for all finishes',
                ],
                'sustainability' => 'White Oak forests are sustainably managed with selective harvesting ensuring natural regeneration and biodiversity.',
                'applications' => ['Kitchen Cabinetry', 'Commercial Furniture', 'Architectural Millwork', 'Conference Tables', 'Reception Areas', 'Hospitality Fixtures'],
                'position' => 4,
                'featured' => false,
                'is_published' => true,
            ],
            [
                'name' => 'Red Oak',
                'scientific_name' => 'Quercus rubra',
                'description' => 'Versatile hardwood with pronounced grain patterns and excellent finishing properties.',
                'content' => 'Red Oak features a reddish-brown heartwood with a pink to red cast and nearly white sapwood. The wood has a coarse, open grain structure with prominent pores and medullary rays. Its porous nature makes it ideal for staining, allowing for rich color variations while maintaining the natural grain character.',
                'featured_image' => 'images/materials/red-oak.jpg',
                'gallery' => ['images/materials/red-oak-1.jpg', 'images/materials/red-oak-2.jpg', 'images/materials/red-oak-3.jpg'],
                'properties' => [
                    'Hardness' => 'Hard (1290 lbf)',
                    'Density' => '0.63 g/cm³',
                    'Stability' => 'Good',
                    'Workability' => 'Good',
                    'Grain' => 'Open grain with prominent patterns',
                    'Finish' => 'Takes stain exceptionally well',
                ],
                'sustainability' => 'Red Oak is one of the most abundant hardwoods in North America, sustainably harvested with responsible forest management.',
                'applications' => ['Traditional Cabinetry', 'Furniture Components', 'Interior Doors', 'Moldings & Trim', 'Rustic Furniture', 'Architectural Elements'],
                'position' => 5,
                'featured' => false,
                'is_published' => true,
            ],
            [
                'name' => 'Ash',
                'scientific_name' => 'Fraxinus americana',
                'description' => 'Strong, flexible hardwood with excellent shock resistance and workability.',
                'content' => 'Ash is a light-colored hardwood with excellent strength-to-weight ratio and flexibility. The heartwood is light brown to medium brown, while the sapwood is light-colored or white. Known for its straight grain and coarse, uniform texture, ash is prized for its exceptional shock resistance and bending properties.',
                'featured_image' => 'images/materials/ash.jpg',
                'gallery' => ['images/materials/ash-1.jpg', 'images/materials/ash-2.jpg', 'images/materials/ash-3.jpg'],
                'properties' => [
                    'Hardness' => 'Hard (1320 lbf)',
                    'Density' => '0.60 g/cm³',
                    'Stability' => 'Good',
                    'Workability' => 'Excellent',
                    'Grain' => 'Straight with coarse texture',
                    'Finish' => 'Good finishing properties',
                ],
                'sustainability' => 'Ash forests are managed sustainably, though monitoring is ongoing due to emerald ash borer concerns in some regions.',
                'applications' => ['Contemporary Furniture', 'Kitchen Cabinets', 'Bent Components', 'Commercial Seating', 'Tool Handles', 'Architectural Details'],
                'position' => 6,
                'featured' => false,
                'is_published' => true,
            ],
            [
                'name' => 'Hickory',
                'scientific_name' => 'Carya species',
                'description' => 'Extremely hard and strong wood with dramatic color variation and rustic character.',
                'content' => 'Hickory is one of the hardest and strongest North American hardwoods. It features dramatic color variation between the light sapwood and dark heartwood, creating striking natural contrast. The wood has a straight grain with occasional waves and a coarse texture that adds rustic character to any project.',
                'featured_image' => 'images/materials/hickory.jpg',
                'gallery' => ['images/materials/hickory-1.jpg', 'images/materials/hickory-2.jpg', 'images/materials/hickory-3.jpg'],
                'properties' => [
                    'Hardness' => 'Very Hard (1820 lbf)',
                    'Density' => '0.72 g/cm³',
                    'Stability' => 'Good with proper drying',
                    'Workability' => 'Challenging due to hardness',
                    'Grain' => 'Straight with dramatic color variation',
                    'Finish' => 'Natural oils enhance color contrast',
                ],
                'sustainability' => 'Hickory is sustainably harvested across its native range with responsible forest management ensuring regeneration.',
                'applications' => ['Rustic Cabinetry', 'Country Furniture', 'Accent Pieces', 'Heavy-Duty Components', 'Rustic Millwork', 'Statement Furniture'],
                'position' => 7,
                'featured' => false,
                'is_published' => true,
            ],
            [
                'name' => 'Poplar',
                'scientific_name' => 'Liriodendron tulipifera',
                'description' => 'Lightweight, stable hardwood ideal for painted finishes and utility applications.',
                'content' => 'Poplar is a versatile hardwood known for its workability and paint-holding properties. The heartwood is light cream to yellowish brown, often with green or purple streaks, while the sapwood is white to cream. Its straight grain and fine, uniform texture make it excellent for painted applications.',
                'featured_image' => 'images/materials/poplar.jpg',
                'gallery' => ['images/materials/poplar-1.jpg', 'images/materials/poplar-2.jpg', 'images/materials/poplar-3.jpg'],
                'properties' => [
                    'Hardness' => 'Soft to Medium (540 lbf)',
                    'Density' => '0.42 g/cm³',
                    'Stability' => 'Excellent',
                    'Workability' => 'Excellent',
                    'Grain' => 'Straight with fine texture',
                    'Finish' => 'Excellent for paint applications',
                ],
                'sustainability' => 'Poplar is one of the most abundant and fast-growing hardwoods, making it highly sustainable with excellent regeneration rates.',
                'applications' => ['Painted Cabinetry', 'Cabinet Boxes', 'Utility Components', 'Interior Trim', 'Furniture Frames', 'Secondary Wood Applications'],
                'position' => 8,
                'featured' => false,
                'is_published' => true,
            ],
        ];

        foreach ($materials as $materialData) {
            $materialData['slug'] = Str::slug($materialData['name']);
            Material::updateOrCreate(
                ['slug' => $materialData['slug']],
                $materialData
            );
        }

        $this->command->info('✓ Materials seeded: ' . Material::count());
    }

    /**
     * Seed FAQs
     */
    protected function seedFaqs(): void
    {
        $this->command->info('Seeding FAQs...');

        $faqs = [
            // Residential Materials FAQs
            ['question' => 'Which material is best for kitchen cabinet boxes?', 'answer' => 'For kitchen cabinet boxes, we typically recommend furniture-grade plywood. It offers excellent structural strength, holds screws well for mounting hardware, and resists moisture better than MDF or particle board. In areas prone to water exposure (like under sinks), plywood or marine-grade plywood is particularly important for longevity.', 'category' => 'materials'],
            ['question' => "What's the most durable option for cabinet doors?", 'answer' => 'For durability in cabinet doors, solid hardwood (like maple or oak) is excellent for stained finishes, while MDF provides superior performance for painted finishes. For extremely high-wear situations, doors faced with high-pressure laminate offer exceptional resistance to scratching, moisture, and staining.', 'category' => 'materials'],
            ['question' => 'How do I choose between painted vs. stained finishes?', 'answer' => "This choice affects your material selection. For stained finishes that showcase wood grain, select hardwoods with attractive grain patterns like oak, cherry, or walnut. For painted finishes, smooth-grained woods like maple or materials like MDF work best since they don't show grain through the paint, creating a more uniform appearance.", 'category' => 'materials'],
            ['question' => 'Are there eco-friendly material options?', 'answer' => "Yes, we offer several eco-friendly options including FSC-certified woods, plywood with formaldehyde-free adhesives, and locally-sourced hardwoods that reduce transportation impact. We can also incorporate reclaimed or repurposed wood for specific design elements, adding both sustainability and unique character to your project.", 'category' => 'materials'],
            ['question' => 'How much should I budget for materials?', 'answer' => "Material costs typically represent about 30-40% of a custom cabinetry project's total budget. Higher-end materials like premium hardwoods, specialty veneers, or designer hardware will increase this percentage. We work with you to allocate your material budget strategically, investing in quality where it matters most for durability and visual impact.", 'category' => 'materials'],
            ['question' => 'Can different materials be mixed in the same project?', 'answer' => 'Absolutely! In fact, mixing materials often creates the optimal balance of performance, aesthetics, and value. We commonly use plywood for structural components, with solid wood for doors and trim, and perhaps specialty materials for accent elements. This strategic mixing maximizes the benefits of each material where it matters most.', 'category' => 'materials'],

            // Custom Furniture FAQs
            ['question' => 'How long does it take to create a custom furniture piece?', 'answer' => "Timeline varies based on complexity, size, and our current production schedule. A simple side table might take 6-8 weeks, while a complex dining table with intricate details could require 10-14 weeks. We'll provide a specific timeline during our consultation based on your project's unique requirements.", 'category' => 'design'],
            ['question' => 'What types of wood do you work with?', 'answer' => "We work with a wide variety of domestic and select exotic hardwoods. Popular choices include walnut, cherry, maple, oak, and ash. Each species offers distinctive characteristics in grain pattern, color, and workability. We help you select the perfect wood based on your aesthetic preferences, budget, and the furniture's intended use.", 'category' => 'materials'],
            ['question' => 'Can you match existing furniture or architectural elements?', 'answer' => 'Yes, we excel at creating pieces that complement existing furniture or architectural features in your home. Whether matching a specific wood species, finish color, or design elements, we can create furniture that integrates seamlessly with your current surroundings while adding its own unique character.', 'category' => 'design'],
            ['question' => 'How do I maintain my custom furniture?', 'answer' => "Each piece comes with specific care instructions based on its materials and finishes. Generally, we recommend keeping wood furniture away from direct sunlight and heat sources, using coasters for drinks, dusting with a soft cloth, and applying quality furniture wax or polish periodically. Proper care ensures your piece will remain beautiful for generations.", 'category' => 'maintenance'],
            ['question' => 'What is your pricing structure for custom furniture?', 'answer' => "Custom furniture pricing reflects the materials, complexity, size, and labor involved. We provide transparent pricing during our proposal phase after understanding your specific needs. While custom furniture represents a higher investment than mass-produced alternatives, it offers superior quality, perfect fit for your space, and lasting value that often makes it more economical over time.", 'category' => 'pricing'],
            ['question' => 'Do you offer a warranty on custom furniture?', 'answer' => 'Yes, we stand behind our craftsmanship with a limited lifetime warranty against defects in workmanship under normal use. This reflects our confidence in the quality of materials and construction techniques we employ. Natural characteristics of wood (like seasonal movement) are not covered, as these are inherent properties of this beautiful natural material.', 'category' => 'warranty'],

            // Commercial Millwork FAQs
            ['question' => 'What types of commercial projects do you handle?', 'answer' => 'We specialize in a wide range of commercial environments including corporate offices, retail spaces, restaurants, hotels, healthcare facilities, and educational institutions. Our expertise includes reception desks, wall paneling, custom cabinetry, point-of-purchase displays, and specialty fixtures designed for your specific business needs.', 'category' => 'general'],
            ['question' => 'How do you ensure durability for high-traffic commercial environments?', 'answer' => "We select materials specifically engineered for commercial use, including commercial-grade hardware, high-pressure laminates, and durable finishes that withstand heavy use. Our construction methods prioritize structural integrity, and we can recommend specific materials based on your environment's unique demands, whether that's moisture resistance for food service areas or impact resistance for retail displays.", 'category' => 'materials'],
            ['question' => 'Can you work with our architects and contractors?', 'answer' => "Absolutely. We excel at collaboration and regularly work with architects, designers, and general contractors. We can join your team at any project stage, providing everything from design assistance to installation. Our detailed shop drawings and clear communication ensure seamless coordination with other trades and adherence to your project's overall vision.", 'category' => 'process'],
            ['question' => 'What is your typical timeline for commercial projects?', 'answer' => 'Timelines vary based on project scope, complexity, and current production capacity. Generally, medium-sized projects move from approval to installation in 8-12 weeks. Larger projects may require 12-16 weeks or more. We prioritize critical path items and can often accommodate phased deliveries to align with construction schedules or minimize business disruption.', 'category' => 'process'],
            ['question' => 'How do you address budget constraints?', 'answer' => "We offer value engineering solutions that maintain design intent while optimizing costs. This might include alternative materials, simplified construction methods, or strategic decisions about where to allocate resources for maximum impact. We're transparent about costs throughout the process and can provide options at different price points to help you make informed decisions.", 'category' => 'pricing'],
            ['question' => 'Do you handle installation nationwide?', 'answer' => 'We perform installations within a 250-mile radius of our workshop. For projects beyond this area, we can either coordinate with local installers or prepare millwork in modular components with detailed instructions for installation by your general contractor. Our pieces are designed for efficient assembly regardless of who performs the installation.', 'category' => 'installation'],
        ];

        $position = 1;
        foreach ($faqs as $faqData) {
            $faqData['position'] = $position++;
            $faqData['is_published'] = true;
            $faqData['status'] = 'published';
            Faq::updateOrCreate(
                ['question' => $faqData['question']],
                $faqData
            );
        }

        $this->command->info('✓ FAQs seeded: ' . Faq::count());
    }

    /**
     * Seed Journals
     */
    protected function seedJournals(): void
    {
        $this->command->info('Seeding Journals...');

        $journals = [
            [
                'title' => 'The Art of Wood Selection: Finding Your Perfect Match',
                'slug' => 'selecting-wood-species',
                'excerpt' => 'A guide to choosing the ideal wood species for your project\'s aesthetic and functional needs.',
                'content' => "Choosing the right wood species is one of the most important decisions in any woodworking project. The wood you select affects not only the appearance of the final piece but also its durability, workability, and how it will age over time.\n\n## Understanding Wood Characteristics\n\nEach wood species has unique characteristics that make it suitable for different applications. Factors to consider include hardness, grain pattern, color, stability, and how the wood responds to various finishes.\n\n## Popular Species for Cabinetry\n\nFor kitchen and bathroom cabinetry, we often recommend maple, cherry, or white oak. Each offers excellent durability and takes finishes beautifully. Maple is ideal for painted finishes, while cherry develops a rich patina over time.\n\n## Furniture Considerations\n\nFor furniture, walnut is a perennial favorite due to its rich color and stunning grain. Cherry offers warmth, while white oak provides strength and distinctive ray patterns. The choice often depends on the style of the piece and the room it will inhabit.",
                'featured_image' => 'images/journal/BlackWalnut_Cabinet_door.png',
                'category' => 'materials',
                'tags' => ['wood selection', 'materials', 'craftsmanship'],
                'is_published' => true,
                'published_at' => now()->subDays(30),
                'status' => 'published',
            ],
            [
                'title' => "Craftsman's Arsenal: Essential Woodworking Tools",
                'slug' => 'tools-of-trade',
                'excerpt' => 'Exploring the specialized tools that enable precision woodworking and fine joinery.',
                'content' => "Behind every masterfully crafted piece of furniture lies an arsenal of specialized tools. From traditional hand tools passed down through generations to modern precision machinery, the right tools enable the fine work that distinguishes custom woodworking.\n\n## Hand Tools\n\nDespite advances in machinery, hand tools remain essential. Hand planes create surfaces that no machine can match. Chisels allow for intricate joinery work. And hand saws provide the control needed for delicate cuts.\n\n## Power Tools\n\nModern precision power tools allow us to work more efficiently while maintaining the highest standards. CNC machines can execute complex designs with perfect consistency, while traditional table saws and routers handle everyday tasks.\n\n## The Best of Both Worlds\n\nThe finest work often combines both approaches—using machines for efficiency and hand tools for the finishing touches that make each piece unique.",
                'featured_image' => 'images/journal/Workshop_closeup_materials.png',
                'category' => 'craftsmanship',
                'tags' => ['tools', 'workshop', 'craftsmanship'],
                'is_published' => true,
                'published_at' => now()->subDays(45),
                'status' => 'published',
            ],
            [
                'title' => 'Crafting Sustainability: Eco-Conscious Woodworking',
                'slug' => 'sustainable-woodworking',
                'excerpt' => 'How we balance beautiful craftsmanship with environmental responsibility and resource conservation.',
                'content' => "Sustainability isn't just a buzzword—it's a responsibility that every craftsman should embrace. At TCS Woodworking, we've made conscious choices to minimize our environmental impact while creating pieces that will last for generations.\n\n## Responsible Sourcing\n\nWe work exclusively with suppliers who practice sustainable forestry. This means selecting wood from well-managed forests where harvesting is done responsibly and replanting ensures future growth.\n\n## Minimizing Waste\n\nOur shop practices are designed to minimize waste. Offcuts find new life in smaller projects or are donated to local schools and makers. Sawdust and shavings go to local farms for composting.\n\n## Building for Longevity\n\nPerhaps the most sustainable practice is building pieces that last. When furniture is built to be handed down through generations, it reduces the need for replacement and the resources that would require.",
                'featured_image' => 'images/journal/A_pile_of_materials_ready_to_be_made_into_cabinet workshop.png',
                'category' => 'sustainability',
                'tags' => ['sustainability', 'eco-friendly', 'responsible sourcing'],
                'is_published' => true,
                'published_at' => now()->subDays(60),
                'status' => 'published',
            ],
        ];

        foreach ($journals as $journalData) {
            Journal::updateOrCreate(
                ['slug' => $journalData['slug']],
                $journalData
            );
        }

        $this->command->info('✓ Journals seeded: ' . Journal::count());
    }
}
