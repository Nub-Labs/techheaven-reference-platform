<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /** Populated during run(); OrderSeeder reads this. */
    public static array $customerIds = [];

    // ── Name pools ────────────────────────────────────────────────────────────

    private array $maleFirstNames = [
        'James','John','Robert','Michael','William','David','Richard','Joseph','Thomas','Charles',
        'Christopher','Daniel','Matthew','Anthony','Mark','Donald','Steven','Paul','Andrew','Joshua',
        'Kenneth','Kevin','Brian','George','Edward','Ronald','Timothy','Jason','Jeffrey','Ryan',
        'Jacob','Gary','Nicholas','Eric','Jonathan','Stephen','Larry','Justin','Scott','Brandon',
        'Frank','Benjamin','Gregory','Samuel','Raymond','Patrick','Alexander','Jack','Dennis','Jerry',
        'Tyler','Aaron','Henry','Douglas','Jose','Adam','Peter','Nathan','Zachary','Walter',
        'Kyle','Harold','Carl','Jeremy','Keith','Roger','Gerald','Ethan','Arthur','Terry',
        'Christian','Sean','Austin','Lawrence','Joe','Dylan','Jesse','Bryan','Billy','Jordan',
        'Albert','Willie','Louis','Phillip','Victor','Randy','Vincent','Russell','Roy','Bobby',
        'Alan','Wayne','Juan','Howard','Harry','Fred','Clarence','Johnny','Philip','Leonard',
        'Liam','Noah','Oliver','Elijah','Lucas','Mason','Logan','Caleb','Owen','Carter',
        'Ryan','Hunter','Wyatt','Sebastian','Leo','Isaiah','Eli','Landon','Adrian','Gavin',
        'Connor','Miles','Nolan','Evan','Aiden','Cameron','Blake','Chase','Xavier','Cole',
    ];

    private array $femaleFirstNames = [
        'Mary','Patricia','Jennifer','Linda','Barbara','Elizabeth','Susan','Jessica','Sarah','Karen',
        'Lisa','Nancy','Betty','Margaret','Sandra','Ashley','Dorothy','Kimberly','Emily','Donna',
        'Michelle','Carol','Amanda','Melissa','Deborah','Stephanie','Rebecca','Sharon','Laura','Cynthia',
        'Kathleen','Amy','Angela','Shirley','Anna','Brenda','Pamela','Emma','Nicole','Helen',
        'Samantha','Katherine','Christine','Debra','Rachel','Carolyn','Janet','Catherine','Maria','Heather',
        'Diane','Julie','Joyce','Victoria','Kelly','Christina','Ruth','Joan','Virginia','Lauren',
        'Judith','Olivia','Megan','Cheryl','Andrea','Megan','Hannah','Jacqueline','Martha','Gloria',
        'Teresa','Ann','Sara','Madison','Frances','Kathryn','Janice','Jean','Abigail','Alice',
        'Julia','Judy','Grace','Denise','Amber','Doris','Marilyn','Danielle','Beverly','Isabella',
        'Theresa','Diana','Natalie','Brittany','Charlotte','Marie','Kayla','Alexis','Lori','Sofia',
        'Avery','Ella','Scarlett','Chloe','Violet','Aurora','Stella','Nora','Ellie','Aria',
        'Zoey','Penelope','Lillian','Layla','Riley','Zoe','Naomi','Leah','Hazel','Willow',
        'Eleanor','Luna','Aubrey','Evelyn','Brooklyn','Savannah','Claire','Skylar','Paisley','Everly',
    ];

    private array $lastNames = [
        'Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez',
        'Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin',
        'Lee','Perez','Thompson','White','Harris','Sanchez','Clark','Ramirez','Lewis','Robinson',
        'Walker','Young','Allen','King','Wright','Scott','Torres','Nguyen','Hill','Flores',
        'Green','Adams','Nelson','Baker','Hall','Rivera','Campbell','Mitchell','Carter','Roberts',
        'Gomez','Phillips','Evans','Turner','Diaz','Parker','Cruz','Edwards','Collins','Reyes',
        'Stewart','Morris','Morales','Murphy','Cook','Rogers','Gutierrez','Ortiz','Morgan','Cooper',
        'Peterson','Bailey','Reed','Kelly','Howard','Ramos','Kim','Cox','Ward','Richardson',
        'Watson','Brooks','Chavez','Wood','James','Bennett','Gray','Mendoza','Ruiz','Hughes',
        'Price','Alvarez','Castillo','Sanders','Patel','Myers','Long','Ross','Foster','Jimenez',
        'Powell','Jenkins','Perry','Russell','Sullivan','Bell','Coleman','Butler','Henderson','Barnes',
        'Gonzales','Fisher','Vasquez','Simmons','Romero','Jordan','Patterson','Alexander','Hamilton','Graham',
        'Reynolds','Griffin','Wallace','Moreno','West','Cole','Hayes','Bryant','Herrera','Gibson',
        'Ellis','Tran','Medina','Aguilar','Stevens','Murray','Ford','Castro','Marshall','Owens',
        'Harrison','Fernandez','McDonald','Woods','Washington','Kennedy','Wells','Webb','Simpson','Tucker',
    ];

    // ── Email domains by country ──────────────────────────────────────────────

    private array $usDomains    = ['gmail.com','yahoo.com','outlook.com','icloud.com','hotmail.com'];
    private array $ukDomains    = ['gmail.com','yahoo.co.uk','outlook.com','hotmail.co.uk','icloud.com'];
    private array $caDomains    = ['gmail.com','yahoo.ca','outlook.com','hotmail.com','icloud.com'];
    private array $auDomains    = ['gmail.com','yahoo.com.au','outlook.com','hotmail.com','icloud.com'];
    private array $deDomains    = ['gmail.com','yahoo.de','outlook.de','hotmail.de','icloud.com'];

    // ── US addresses ─────────────────────────────────────────────────────────

    private array $usAddresses = [
        ['city' => 'New York',      'state' => 'NY', 'postcode' => '10001'],
        ['city' => 'Los Angeles',   'state' => 'CA', 'postcode' => '90001'],
        ['city' => 'Chicago',       'state' => 'IL', 'postcode' => '60601'],
        ['city' => 'Houston',       'state' => 'TX', 'postcode' => '77001'],
        ['city' => 'Phoenix',       'state' => 'AZ', 'postcode' => '85001'],
        ['city' => 'Philadelphia',  'state' => 'PA', 'postcode' => '19101'],
        ['city' => 'San Antonio',   'state' => 'TX', 'postcode' => '78201'],
        ['city' => 'San Diego',     'state' => 'CA', 'postcode' => '92101'],
        ['city' => 'Dallas',        'state' => 'TX', 'postcode' => '75201'],
        ['city' => 'San Jose',      'state' => 'CA', 'postcode' => '95101'],
        ['city' => 'Austin',        'state' => 'TX', 'postcode' => '78701'],
        ['city' => 'Jacksonville',  'state' => 'FL', 'postcode' => '32099'],
        ['city' => 'Fort Worth',    'state' => 'TX', 'postcode' => '76101'],
        ['city' => 'Columbus',      'state' => 'OH', 'postcode' => '43085'],
        ['city' => 'Charlotte',     'state' => 'NC', 'postcode' => '28201'],
        ['city' => 'Indianapolis',  'state' => 'IN', 'postcode' => '46201'],
        ['city' => 'San Francisco', 'state' => 'CA', 'postcode' => '94102'],
        ['city' => 'Seattle',       'state' => 'WA', 'postcode' => '98101'],
        ['city' => 'Denver',        'state' => 'CO', 'postcode' => '80201'],
        ['city' => 'Nashville',     'state' => 'TN', 'postcode' => '37201'],
        ['city' => 'Oklahoma City', 'state' => 'OK', 'postcode' => '73101'],
        ['city' => 'El Paso',       'state' => 'TX', 'postcode' => '79901'],
        ['city' => 'Washington',    'state' => 'DC', 'postcode' => '20001'],
        ['city' => 'Las Vegas',     'state' => 'NV', 'postcode' => '89101'],
        ['city' => 'Boston',        'state' => 'MA', 'postcode' => '02101'],
        ['city' => 'Portland',      'state' => 'OR', 'postcode' => '97201'],
        ['city' => 'Atlanta',       'state' => 'GA', 'postcode' => '30301'],
        ['city' => 'Miami',         'state' => 'FL', 'postcode' => '33101'],
        ['city' => 'Minneapolis',   'state' => 'MN', 'postcode' => '55401'],
        ['city' => 'Tucson',        'state' => 'AZ', 'postcode' => '85701'],
    ];

    private array $usStreets = [
        'Main St','Oak Ave','Maple Dr','Cedar Ln','Pine St','Elm St','Washington Blvd',
        'Park Ave','Lake Dr','River Rd','Sunset Blvd','Highland Ave','Forest Dr','Valley Rd',
        'Spring St','Meadow Ln','Hill Rd','Church St','College Ave','Broadway','Center St',
        'Union St','Lincoln Ave','Jefferson St','Adams St','Madison Ave','Monroe St','Grant Ave',
    ];

    // ── UK addresses ─────────────────────────────────────────────────────────

    private array $ukAddresses = [
        ['city' => 'London',        'state' => 'England',          'postcode' => 'EC1A 1BB'],
        ['city' => 'Manchester',    'state' => 'England',          'postcode' => 'M1 1AE'],
        ['city' => 'Birmingham',    'state' => 'England',          'postcode' => 'B1 1BB'],
        ['city' => 'Glasgow',       'state' => 'Scotland',         'postcode' => 'G1 1AP'],
        ['city' => 'Leeds',         'state' => 'England',          'postcode' => 'LS1 1BA'],
        ['city' => 'Edinburgh',     'state' => 'Scotland',         'postcode' => 'EH1 1AA'],
        ['city' => 'Liverpool',     'state' => 'England',          'postcode' => 'L1 8JQ'],
        ['city' => 'Bristol',       'state' => 'England',          'postcode' => 'BS1 1AA'],
        ['city' => 'Sheffield',     'state' => 'England',          'postcode' => 'S1 1AA'],
        ['city' => 'Cardiff',       'state' => 'Wales',            'postcode' => 'CF10 1AA'],
        ['city' => 'Belfast',       'state' => 'Northern Ireland', 'postcode' => 'BT1 1AB'],
        ['city' => 'Nottingham',    'state' => 'England',          'postcode' => 'NG1 1AA'],
        ['city' => 'Leicester',     'state' => 'England',          'postcode' => 'LE1 1AA'],
        ['city' => 'Coventry',      'state' => 'England',          'postcode' => 'CV1 1AA'],
        ['city' => 'Cambridge',     'state' => 'England',          'postcode' => 'CB1 1AA'],
    ];

    private array $ukStreets = [
        'High Street','King Street','Church Road','Victoria Road','Green Lane','Mill Lane',
        'Station Road','Park Road','Manor Road','London Road','Queens Road','North Street',
        'South Street','West Street','East Street','Bridge Street','Hill Street','Market Street',
    ];

    // ── Canada addresses ──────────────────────────────────────────────────────

    private array $caAddresses = [
        ['city' => 'Toronto',    'state' => 'ON', 'postcode' => 'M5H 1A1'],
        ['city' => 'Vancouver',  'state' => 'BC', 'postcode' => 'V6B 1A1'],
        ['city' => 'Montreal',   'state' => 'QC', 'postcode' => 'H2Y 1A1'],
        ['city' => 'Calgary',    'state' => 'AB', 'postcode' => 'T2P 1A1'],
        ['city' => 'Ottawa',     'state' => 'ON', 'postcode' => 'K1P 1A1'],
        ['city' => 'Edmonton',   'state' => 'AB', 'postcode' => 'T5J 1A1'],
        ['city' => 'Winnipeg',   'state' => 'MB', 'postcode' => 'R3C 1A1'],
        ['city' => 'Quebec City','state' => 'QC', 'postcode' => 'G1R 1A1'],
        ['city' => 'Hamilton',   'state' => 'ON', 'postcode' => 'L8P 1A1'],
        ['city' => 'Halifax',    'state' => 'NS', 'postcode' => 'B3J 1A1'],
    ];

    private array $caStreets = [
        'Yonge Street','Bloor Street','Dundas Street','King Street','Queen Street',
        'Granville Street','Robson Street','Jasper Avenue','Portage Avenue','Spring Garden Road',
        'Rideau Street','Sparks Street','Ste-Catherine Street','Peel Street','Guy Street',
    ];

    // ── Australia addresses ───────────────────────────────────────────────────

    private array $auAddresses = [
        ['city' => 'Sydney',    'state' => 'NSW', 'postcode' => '2000'],
        ['city' => 'Melbourne', 'state' => 'VIC', 'postcode' => '3000'],
        ['city' => 'Brisbane',  'state' => 'QLD', 'postcode' => '4000'],
        ['city' => 'Perth',     'state' => 'WA',  'postcode' => '6000'],
        ['city' => 'Adelaide',  'state' => 'SA',  'postcode' => '5000'],
        ['city' => 'Canberra',  'state' => 'ACT', 'postcode' => '2600'],
        ['city' => 'Hobart',    'state' => 'TAS', 'postcode' => '7000'],
        ['city' => 'Darwin',    'state' => 'NT',  'postcode' => '0800'],
        ['city' => 'Gold Coast','state' => 'QLD', 'postcode' => '4217'],
        ['city' => 'Newcastle', 'state' => 'NSW', 'postcode' => '2300'],
    ];

    private array $auStreets = [
        'George Street','Pitt Street','Market Street','King Street','Queen Street',
        'Swanston Street','Flinders Lane','Collins Street','Bourke Street','Elizabeth Street',
        'Adelaide Street','Edward Street','Mary Street','William Street','Albert Street',
    ];

    // ── Germany addresses ─────────────────────────────────────────────────────

    private array $deAddresses = [
        ['city' => 'Berlin',    'state' => 'Berlin',          'postcode' => '10115'],
        ['city' => 'Hamburg',   'state' => 'Hamburg',         'postcode' => '20095'],
        ['city' => 'Munich',    'state' => 'Bavaria',         'postcode' => '80331'],
        ['city' => 'Cologne',   'state' => 'North Rhine-Westphalia', 'postcode' => '50667'],
        ['city' => 'Frankfurt', 'state' => 'Hesse',           'postcode' => '60311'],
        ['city' => 'Stuttgart', 'state' => 'Baden-Württemberg','postcode' => '70173'],
        ['city' => 'Düsseldorf','state' => 'North Rhine-Westphalia', 'postcode' => '40213'],
        ['city' => 'Leipzig',   'state' => 'Saxony',          'postcode' => '04103'],
        ['city' => 'Dortmund',  'state' => 'North Rhine-Westphalia', 'postcode' => '44135'],
        ['city' => 'Dresden',   'state' => 'Saxony',          'postcode' => '01067'],
    ];

    private array $deStreets = [
        'Hauptstraße','Kirchstraße','Bahnhofstraße','Gartenstraße','Schulstraße',
        'Dorfstraße','Bergstraße','Friedrichstraße','Kantstraße','Kurfürstendamm',
        'Rosenstraße','Lindenstraße','Mozartstraße','Schillerstraße','Goethestraße',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('  → Seeding customers...');

        $now        = now();
        $batchSize  = 50;
        $customers  = [];
        $addresses  = [];
        $total      = 1000;

        // Country distribution weights
        $countryDist = array_merge(
            array_fill(0, 60, 'US'),
            array_fill(0, 15, 'GB'),
            array_fill(0, 10, 'CA'),
            array_fill(0, 8,  'AU'),
            array_fill(0, 7,  'DE'),
        );

        $customerGroupId = DB::table('customer_groups')->value('id') ?? 1;

        for ($i = 1; $i <= $total; $i++) {
            $gender    = ($i % 2 === 0) ? 'male' : 'female';
            $firstName = $gender === 'male'
                ? $this->maleFirstNames[($i * 7 + 13) % count($this->maleFirstNames)]
                : $this->femaleFirstNames[($i * 11 + 17) % count($this->femaleFirstNames)];
            $lastName  = $this->lastNames[($i * 13 + 5) % count($this->lastNames)];

            $country = $countryDist[$i % count($countryDist)];

            // Unique email — use index to avoid collisions
            $emailNum  = $i;
            $domain    = $this->pickDomain($country, $i);
            $email     = strtolower($firstName . '.' . $lastName . $emailNum . '@' . $domain);

            // Age 18–65: dob between ~61 and ~18 years ago
            $ageYears   = 18 + ($i % 48);
            $dobYear    = (int)date('Y') - $ageYears;
            $dobMonth   = ($i % 12) + 1;
            $dobDay     = ($i % 28) + 1;
            $dob        = sprintf('%04d-%02d-%02d', $dobYear, $dobMonth, $dobDay);

            // Created_at spread over last 24 months
            $monthsAgo  = $i % 24;
            $daysAgo    = ($i * 3) % 28;
            $createdAt  = $now->copy()->subMonths($monthsAgo)->subDays($daysAgo)->format('Y-m-d H:i:s');

            $phone = $this->generatePhone($country, $i);

            $customers[] = [
                'channel_id'               => 1,
                'first_name'               => $firstName,
                'last_name'                => $lastName,
                'gender'                   => $gender,
                'date_of_birth'            => $dob,
                'email'                    => $email,
                'phone'                    => $phone,
                'password'                 => Hash::make('Password@123', ['rounds' => 4]),
                'customer_group_id'        => $customerGroupId,
                'is_verified'              => 1,
                'subscribed_to_news_letter' => ($i % 10 < 7) ? 1 : 0,
                'is_suspended'             => 0,
                'token'                    => null,
                'created_at'               => $createdAt,
                'updated_at'               => $createdAt,
            ];
        }

        // Insert in batches and collect IDs
        foreach (array_chunk($customers, $batchSize) as $batch) {
            DB::table('customers')->insert($batch);
        }

        // Load inserted IDs
        $rows = DB::table('customers')
            ->whereIn('email', array_column($customers, 'email'))
            ->select('id', 'email', 'first_name', 'last_name', 'phone')
            ->get()
            ->keyBy('email');

        self::$customerIds = $rows->pluck('id')->toArray();

        // Build addresses
        $addrIndex = 0;
        foreach ($customers as $idx => $c) {
            $row      = $rows[$c['email']] ?? null;
            if (!$row) {
                continue;
            }
            $custId   = $row->id;
            $country  = $countryDist[($idx + 1) % count($countryDist)];
            $numAddrs = (($idx + 1) % 3 === 0) ? 2 : 1;

            for ($a = 0; $a < $numAddrs; $a++) {
                [$addrCity, $addrState, $postcode, $street] = $this->pickAddress($country, $addrIndex + $a);
                $streetNum = ($addrIndex * 3 + $a * 7 + 1) % 999 + 1;

                $addresses[] = [
                    'address_type'    => 'customer',
                    'customer_id'     => $custId,
                    'first_name'      => $c['first_name'],
                    'last_name'       => $c['last_name'],
                    'company_name'    => null,
                    'address'         => $streetNum . ' ' . $street,
                    'city'            => $addrCity,
                    'state'           => $addrState,
                    'country'         => $country,
                    'postcode'        => $postcode,
                    'phone'           => $c['phone'],
                    'default_address' => ($a === 0) ? 1 : 0,
                    'created_at'      => $c['created_at'],
                    'updated_at'      => $c['created_at'],
                ];
            }

            $addrIndex += $numAddrs;
        }

        foreach (array_chunk($addresses, $batchSize) as $batch) {
            DB::table('addresses')->insert($batch);
        }

        $this->command->info('     Created ' . count(self::$customerIds) . ' customers, ' . count($addresses) . ' addresses');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function pickDomain(string $country, int $seed): string
    {
        return match ($country) {
            'GB' => $this->ukDomains[$seed % count($this->ukDomains)],
            'CA' => $this->caDomains[$seed % count($this->caDomains)],
            'AU' => $this->auDomains[$seed % count($this->auDomains)],
            'DE' => $this->deDomains[$seed % count($this->deDomains)],
            default => $this->usDomains[$seed % count($this->usDomains)],
        };
    }

    private function pickAddress(string $country, int $seed): array
    {
        return match ($country) {
            'GB' => [
                $this->ukAddresses[$seed % count($this->ukAddresses)]['city'],
                $this->ukAddresses[$seed % count($this->ukAddresses)]['state'],
                $this->ukAddresses[$seed % count($this->ukAddresses)]['postcode'],
                $this->ukStreets[$seed % count($this->ukStreets)],
            ],
            'CA' => [
                $this->caAddresses[$seed % count($this->caAddresses)]['city'],
                $this->caAddresses[$seed % count($this->caAddresses)]['state'],
                $this->caAddresses[$seed % count($this->caAddresses)]['postcode'],
                $this->caStreets[$seed % count($this->caStreets)],
            ],
            'AU' => [
                $this->auAddresses[$seed % count($this->auAddresses)]['city'],
                $this->auAddresses[$seed % count($this->auAddresses)]['state'],
                $this->auAddresses[$seed % count($this->auAddresses)]['postcode'],
                $this->auStreets[$seed % count($this->auStreets)],
            ],
            'DE' => [
                $this->deAddresses[$seed % count($this->deAddresses)]['city'],
                $this->deAddresses[$seed % count($this->deAddresses)]['state'],
                $this->deAddresses[$seed % count($this->deAddresses)]['postcode'],
                $this->deStreets[$seed % count($this->deStreets)],
            ],
            default => [
                $this->usAddresses[$seed % count($this->usAddresses)]['city'],
                $this->usAddresses[$seed % count($this->usAddresses)]['state'],
                $this->usAddresses[$seed % count($this->usAddresses)]['postcode'],
                $this->usStreets[$seed % count($this->usStreets)],
            ],
        };
    }

    private function generatePhone(string $country, int $seed): string
    {
        $n = abs(($seed * 7919 + 12345) % 9000000000) + 1000000000;
        return match ($country) {
            'GB'    => '+44 ' . substr((string)$n, 0, 4) . ' ' . substr((string)$n, 4, 6),
            'CA'    => '+1 ' . substr((string)$n, 0, 3) . '-' . substr((string)$n, 3, 3) . '-' . substr((string)$n, 6, 4),
            'AU'    => '+61 4' . substr((string)$n, 0, 2) . ' ' . substr((string)$n, 2, 3) . ' ' . substr((string)$n, 5, 3),
            'DE'    => '+49 ' . substr((string)$n, 0, 3) . ' ' . substr((string)$n, 3, 7),
            default => '+1 ' . substr((string)$n, 0, 3) . '-' . substr((string)$n, 3, 3) . '-' . substr((string)$n, 6, 4),
        };
    }
}
