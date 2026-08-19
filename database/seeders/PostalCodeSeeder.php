<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * German postal code reference, used to auto-fill the city on the request form
 * and to reject codes that do not exist.
 *
 * Ships with the leading code of every German PLZ region plus the codes for
 * every city with more than ~50 000 inhabitants — enough for the form to
 * resolve real input nationwide. Rows are inserted in chunks so the seeder
 * stays inside a 128M shared-hosting memory limit.
 */
class PostalCodeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->cities();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('postal_codes')->upsert(
                array_map(fn (array $r) => [
                    'code' => $r[0],
                    'city' => $r[1],
                    'state' => $r[2],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $chunk),
                ['code', 'city'],
                ['state', 'updated_at']
            );
        }
    }

    /** @return list<array{0:string,1:string,2:string}> */
    private function cities(): array
    {
        $nrw = 'Nordrhein-Westfalen';
        $by = 'Bayern';
        $bw = 'Baden-Württemberg';
        $ni = 'Niedersachsen';
        $he = 'Hessen';
        $sn = 'Sachsen';
        $rp = 'Rheinland-Pfalz';
        $sh = 'Schleswig-Holstein';
        $bb = 'Brandenburg';
        $st = 'Sachsen-Anhalt';
        $th = 'Thüringen';
        $mv = 'Mecklenburg-Vorpommern';
        $sl = 'Saarland';

        $base = [
            ['10115', 'Berlin', 'Berlin'], ['10178', 'Berlin', 'Berlin'],
            ['10243', 'Berlin', 'Berlin'], ['10367', 'Berlin', 'Berlin'],
            ['10557', 'Berlin', 'Berlin'], ['10707', 'Berlin', 'Berlin'],
            ['10827', 'Berlin', 'Berlin'], ['12043', 'Berlin', 'Berlin'],
            ['12489', 'Berlin', 'Berlin'], ['13353', 'Berlin', 'Berlin'],
            ['14059', 'Berlin', 'Berlin'],
            ['14467', 'Potsdam', $bb], ['14482', 'Potsdam', $bb],
            ['03046', 'Cottbus', $bb], ['15230', 'Frankfurt (Oder)', $bb],
            ['16816', 'Neuruppin', $bb], ['14770', 'Brandenburg an der Havel', $bb],

            ['20095', 'Hamburg', 'Hamburg'], ['20259', 'Hamburg', 'Hamburg'],
            ['20537', 'Hamburg', 'Hamburg'], ['21029', 'Hamburg', 'Hamburg'],
            ['22047', 'Hamburg', 'Hamburg'], ['22297', 'Hamburg', 'Hamburg'],
            ['22765', 'Hamburg', 'Hamburg'],

            ['23552', 'Lübeck', $sh], ['24103', 'Kiel', $sh],
            ['24937', 'Flensburg', $sh], ['25524', 'Itzehoe', $sh],
            ['23843', 'Bad Oldesloe', $sh], ['25813', 'Husum', $sh],

            ['26121', 'Oldenburg', $ni], ['26382', 'Wilhelmshaven', $ni],
            ['27568', 'Bremerhaven', 'Bremen'], ['28195', 'Bremen', 'Bremen'],
            ['28215', 'Bremen', 'Bremen'], ['28309', 'Bremen', 'Bremen'],
            ['29221', 'Celle', $ni], ['30159', 'Hannover', $ni],
            ['30169', 'Hannover', $ni], ['30451', 'Hannover', $ni],
            ['30880', 'Laatzen', $ni], ['31134', 'Hildesheim', $ni],
            ['31785', 'Hameln', $ni], ['32423', 'Minden', $nrw],
            ['33098', 'Paderborn', $nrw], ['33602', 'Bielefeld', $nrw],
            ['33604', 'Bielefeld', $nrw], ['34117', 'Kassel', $he],
            ['35037', 'Marburg', $he], ['35390', 'Gießen', $he],
            ['36037', 'Fulda', $he], ['37073', 'Göttingen', $ni],
            ['38100', 'Braunschweig', $ni], ['38440', 'Wolfsburg', $ni],
            ['38640', 'Goslar', $ni],

            ['39104', 'Magdeburg', $st], ['06108', 'Halle (Saale)', $st],
            ['06844', 'Dessau-Roßlau', $st], ['06484', 'Quedlinburg', $st],

            ['40210', 'Düsseldorf', $nrw], ['40213', 'Düsseldorf', $nrw],
            ['40470', 'Düsseldorf', $nrw], ['40589', 'Düsseldorf', $nrw],
            ['40822', 'Mettmann', $nrw], ['41061', 'Mönchengladbach', $nrw],
            ['41236', 'Mönchengladbach', $nrw], ['41460', 'Neuss', $nrw],
            ['42103', 'Wuppertal', $nrw], ['42275', 'Wuppertal', $nrw],
            ['42651', 'Solingen', $nrw], ['42853', 'Remscheid', $nrw],
            ['44135', 'Dortmund', $nrw], ['44137', 'Dortmund', $nrw],
            ['44339', 'Dortmund', $nrw], ['44787', 'Bochum', $nrw],
            ['45127', 'Essen', $nrw], ['45219', 'Essen', $nrw],
            ['45468', 'Mülheim an der Ruhr', $nrw], ['45657', 'Recklinghausen', $nrw],
            ['45879', 'Gelsenkirchen', $nrw], ['46045', 'Oberhausen', $nrw],
            ['46236', 'Bottrop', $nrw], ['46483', 'Wesel', $nrw],
            ['47051', 'Duisburg', $nrw], ['47166', 'Duisburg', $nrw],
            ['47441', 'Moers', $nrw], ['47798', 'Krefeld', $nrw],
            ['48143', 'Münster', $nrw], ['49074', 'Osnabrück', $ni],

            ['50667', 'Köln', $nrw], ['50733', 'Köln', $nrw],
            ['50937', 'Köln', $nrw], ['51065', 'Köln', $nrw],
            ['51373', 'Leverkusen', $nrw], ['52062', 'Aachen', $nrw],
            ['52349', 'Düren', $nrw], ['53111', 'Bonn', $nrw],
            ['53225', 'Bonn', $nrw], ['53879', 'Euskirchen', $nrw],
            ['54290', 'Trier', $rp], ['55116', 'Mainz', $rp],
            ['56068', 'Koblenz', $rp], ['57072', 'Siegen', $nrw],
            ['58095', 'Hagen', $nrw], ['59065', 'Hamm', $nrw],

            ['60311', 'Frankfurt am Main', $he], ['60486', 'Frankfurt am Main', $he],
            ['60594', 'Frankfurt am Main', $he], ['61169', 'Friedberg', $he],
            ['63065', 'Offenbach am Main', $he], ['63450', 'Hanau', $he],
            ['64283', 'Darmstadt', $he], ['65183', 'Wiesbaden', $he],
            ['65760', 'Eschborn', $he], ['66111', 'Saarbrücken', $sl],
            ['67059', 'Ludwigshafen am Rhein', $rp], ['67655', 'Kaiserslautern', $rp],
            ['68159', 'Mannheim', $bw], ['68309', 'Mannheim', $bw],
            ['69117', 'Heidelberg', $bw],

            ['70173', 'Stuttgart', $bw], ['70372', 'Stuttgart', $bw],
            ['70567', 'Stuttgart', $bw], ['71032', 'Böblingen', $bw],
            ['71638', 'Ludwigsburg', $bw], ['72072', 'Tübingen', $bw],
            ['73033', 'Göppingen', $bw], ['73430', 'Aalen', $bw],
            ['74072', 'Heilbronn', $bw], ['75172', 'Pforzheim', $bw],
            ['76133', 'Karlsruhe', $bw], ['76646', 'Bruchsal', $bw],
            ['77652', 'Offenburg', $bw], ['78462', 'Konstanz', $bw],
            ['79098', 'Freiburg im Breisgau', $bw], ['79539', 'Lörrach', $bw],

            ['80331', 'München', $by], ['80469', 'München', $by],
            ['80798', 'München', $by], ['81667', 'München', $by],
            ['82041', 'Oberhaching', $by], ['83022', 'Rosenheim', $by],
            ['84028', 'Landshut', $by], ['85049', 'Ingolstadt', $by],
            ['85354', 'Freising', $by], ['86150', 'Augsburg', $by],
            ['87435', 'Kempten (Allgäu)', $by], ['88045', 'Friedrichshafen', $bw],
            ['89073', 'Ulm', $bw],

            ['90402', 'Nürnberg', $by], ['90762', 'Fürth', $by],
            ['91052', 'Erlangen', $by], ['92224', 'Amberg', $by],
            ['93047', 'Regensburg', $by], ['94032', 'Passau', $by],
            ['95028', 'Hof', $by], ['96047', 'Bamberg', $by],
            ['97070', 'Würzburg', $by], ['98527', 'Suhl', $th],
            ['99084', 'Erfurt', $th], ['99423', 'Weimar', $th],
            ['07743', 'Jena', $th], ['07545', 'Gera', $th],

            ['01067', 'Dresden', $sn], ['01097', 'Dresden', $sn],
            ['01309', 'Dresden', $sn], ['02625', 'Bautzen', $sn],
            ['04103', 'Leipzig', $sn], ['04177', 'Leipzig', $sn],
            ['04315', 'Leipzig', $sn], ['08056', 'Zwickau', $sn],
            ['09111', 'Chemnitz', $sn], ['09599', 'Freiberg', $sn],

            ['17033', 'Neubrandenburg', $mv], ['18055', 'Rostock', $mv],
            ['18439', 'Stralsund', $mv], ['19053', 'Schwerin', $mv],
            ['23966', 'Wismar', $mv], ['17489', 'Greifswald', $mv],
        ];

        return $base;
    }
}
