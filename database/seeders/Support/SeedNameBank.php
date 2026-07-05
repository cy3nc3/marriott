<?php

namespace Database\Seeders\Support;

class SeedNameBank
{
    private const STUDENT_FIRST_NAMES = [
        'Abren', 'Adriel', 'Aeson', 'Ailyn', 'Aiven', 'Aldrinel', 'Alira', 'Alven', 'Amielyn', 'Anrio',
        'Arcel', 'Ardin', 'Arenzo', 'Arilyn', 'Arven', 'Ashiel', 'Asira', 'Avel', 'Avrina', 'Aziel',
        'Bailen', 'Brenza', 'Brinel', 'Brio', 'Briven', 'Caelin', 'Caiza', 'Calven', 'Camiel', 'Cariel',
        'Carren', 'Caviel', 'Celyn', 'Cerin', 'Cevin', 'Chaila', 'Chaviel', 'Coriel', 'Cyrel', 'Dailyn',
        'Darenz', 'Daviel', 'Delza', 'Dherin', 'Diara', 'Drevin', 'Drielle', 'Druven', 'Dyren', 'Eilian',
        'Elrin', 'Elvyn', 'Emira', 'Eniel', 'Eralyn', 'Eriven', 'Esail', 'Evin', 'Ezael', 'Fadiel',
        'Faerin', 'Faren', 'Felza', 'Fhyn', 'Fiara', 'Florin', 'Frevyn', 'Fria', 'Gailen', 'Galren',
        'Gavrin', 'Geilyn', 'Gerin', 'Ghael', 'Gialyn', 'Girel', 'Graziel', 'Griven', 'Hailen', 'Hariel',
        'Havyn', 'Heilyn', 'Henzar', 'Heriel', 'Hiryn', 'Iadiel', 'Ialyn', 'Iarven', 'Ielza', 'Ilren',
        'Inver', 'Iraiza', 'Iveryn', 'Izhael', 'Jaelin', 'Jarenz', 'Javiel', 'Jeara', 'Jelrin', 'Jhaiven',
        'Jirel', 'Jovyn', 'Julren', 'Kaelix', 'Kairyn', 'Kalven', 'Karelyn', 'Kaviel', 'Khayrin', 'Kirel',
        'Klaren', 'Klyza', 'Kyrin', 'Ladriel', 'Laelyn', 'Lairen', 'Lareza', 'Laviel', 'Leilyn', 'Lerix',
        'Lharen', 'Lianzo', 'Liriel', 'Lyzel', 'Maelyn', 'Mairen', 'Marvyn', 'Maviel', 'Meira', 'Merin',
        'Mhaila', 'Mirel', 'Mivren', 'Myzael', 'Nadiel', 'Naelyn', 'Nairen', 'Nareza', 'Naviel', 'Neilyn',
        'Nerix', 'Nharen', 'Niazo', 'Niriel', 'Nyzel', 'Oadriel', 'Oaelyn', 'Oairen', 'Oareza', 'Oaviel',
        'Oeilyn', 'Oerix', 'Oharen', 'Oiazo', 'Oiriel', 'Oyzel', 'Paelyn', 'Pairen', 'Parvyn', 'Paviel',
        'Peira', 'Perin', 'Phaila', 'Pirel', 'Pivren', 'Pyzael', 'Qaelyn', 'Qairen', 'Qarvyn', 'Qaviel',
        'Qeira', 'Qerin', 'Qhaila', 'Qirel', 'Raelyn', 'Rairen', 'Raviel', 'Reilyn', 'Rerix', 'Rhaiven',
        'Rirel', 'Rovyn', 'Ryzel', 'Saelyn', 'Sairen', 'Sarvyn', 'Saviel', 'Seira', 'Serin', 'Shaila',
        'Sirel', 'Sivren', 'Syzael', 'Taelyn', 'Tairen', 'Tarvyn', 'Taviel', 'Teira', 'Terin', 'Thaila',
        'Tirel', 'Tivren', 'Tyzael', 'Uaelyn', 'Uairen', 'Uarvyn', 'Uaviel', 'Ueira', 'Uerin', 'Uhaila',
        'Uirel', 'Vaelyn', 'Vairen', 'Varvyn', 'Vaviel', 'Veira', 'Verin', 'Vhaila', 'Virel', 'Vyzael',
    ];

    private const STUDENT_LAST_NAMES = [
        'Abella', 'Adlawan', 'Aganon', 'Alonzo', 'Amador', 'Andrada', 'Arcega', 'Arenas', 'Asistio', 'Atienza',
        'Baldino', 'Balmes', 'Banlag', 'Barasoain', 'Bascuna', 'Bauton', 'Beltejar', 'Bermudez', 'Bernasol', 'Bilugan',
        'Calanay', 'Calud', 'Camrosa', 'Canilao', 'Capuno', 'Carulasan', 'Casul', 'Catubig', 'Celdran', 'Cerilo',
        'Dagsaan', 'Dalandan', 'Daluz', 'Dapitan', 'Darang', 'Dayrit', 'Dejeco', 'Delara', 'Deramas', 'Dionisio',
        'Ebarle', 'Edralin', 'Elumba', 'Embero', 'Endaya', 'Ermino', 'Escurel', 'Estenzo', 'Etulle', 'Evardo',
        'Faburada', 'Falcis', 'Famorca', 'Farolan', 'Fegidero', 'Feliciano', 'Ferido', 'Fuentel', 'Fulgencio', 'Furigay',
        'Gabriana', 'Galero', 'Gamboa', 'Garot', 'Gatpandan', 'Gelonga', 'Geronimo', 'Guballa', 'Guevarra', 'Gurrea',
        'Habacon', 'Halili', 'Hamoy', 'Hapsay', 'Heraldo', 'Hidalgo', 'Hingco', 'Hirang', 'Hocson', 'Huerva',
        'Ibanez', 'Idulog', 'Ignacio', 'Ilagan', 'Imbong', 'Inocian', 'Iral', 'Isagani', 'Isidro', 'Itliong',
        'Jabian', 'Jalandoni', 'Jampason', 'Jarin', 'Javillonar', 'Jeron', 'Jocson', 'Jumalon', 'Justino', 'Juyad',
        'Kabigting', 'Kahulugan', 'Kalaw', 'Kamagong', 'Kanlaon', 'Kasilag', 'Katigbak', 'Kilates', 'Kintanar', 'Koronel',
        'Labordo', 'Lacaran', 'Ladaran', 'Lagasca', 'Lambino', 'Lardizabal', 'Larin', 'Laxina', 'Legarda', 'Libatique',
        'Mabansag', 'Mactan', 'Madriaga', 'Maglinao', 'Maliksi', 'Mancao', 'Marang', 'Mataro', 'Mayuga', 'Mendoza',
        'Nacario', 'Nadal', 'Nagera', 'Naligod', 'Napiere', 'Nasino', 'Natividad', 'Nerona', 'Nispero', 'Nulud',
        'Obusan', 'Ocampa', 'Oguis', 'Olazo', 'Olivares', 'Orbeta', 'Ortila', 'Ositan', 'Otares', 'Oyanguren',
        'Pabalan', 'Pacis', 'Pagdanganan', 'Pajares', 'Palad', 'Pangilinan', 'Paraiso', 'Pascual', 'Patacsil', 'Pecson',
        'Quibal', 'Quibuyen', 'Quimbo', 'Quinones', 'Quitoriano', 'Rabena', 'Ragodon', 'Ramoso', 'Rebulta', 'Recto',
        'Sabado', 'Sagun', 'Salonga', 'Samonte', 'Sanidad', 'Sarmiento', 'Segovia', 'Sibayan', 'Sinag', 'Sorio',
        'Tabangay', 'Tagle', 'Talampas', 'Tambis', 'Tanedo', 'Tapia', 'Tariq', 'Templo', 'Tibayan', 'Tolentino',
        'Ubaldo', 'Umalin', 'Unda', 'Urbiztondo', 'Valmoria', 'Ventura', 'Villareal', 'Wagas', 'Yparraguirre', 'Zarate',
    ];

    private const STUDENT_MIDDLE_NAMES = [
        'Abarquez', 'Abesamis', 'Abordo', 'Adlawan', 'Agbayani', 'Aguinaldo', 'Alcantara', 'Almario', 'Andal', 'Arandia',
        'Bacalso', 'Balingit', 'Baluyot', 'Banawa', 'Basilio', 'Batungbakal', 'Baylon', 'Belmonte', 'Benedicto', 'Buenafe',
        'Cabaluna', 'Cabrera', 'Calalang', 'Calumpang', 'Camacho', 'Canlas', 'Carandang', 'Castaneda', 'Cayetano', 'Cordero',
        'Dacumos', 'Dalisay', 'Dela Cruz', 'Delos Santos', 'Dimaano', 'Dimaculangan', 'Dizon', 'Domingo', 'Dumlao', 'Dytioco',
        'Echavez', 'Elizalde', 'Enriquez', 'Escalante', 'Espina', 'Estrella', 'Fabian', 'Fajardo', 'Ferrer', 'Florentino',
        'Galang', 'Gatchalian', 'Gonzales', 'Guerrero', 'Guinto', 'Halcon', 'Hernandez', 'Ignacio', 'Ilagan', 'Jalandoni',
        'Labayen', 'Lacanilao', 'Lagdameo', 'Lansangan', 'Laurel', 'Lazaro', 'Limbaga', 'Lozada', 'Macapagal', 'Magsino',
        'Malabanan', 'Manalastas', 'Mangubat', 'Marasigan', 'Mercado', 'Natividad', 'Navarro', 'Ocampo', 'Ongpin', 'Panganiban',
        'Pascual', 'Pineda', 'Quintana', 'Rabino', 'Reyes', 'Rivera', 'Salcedo', 'Santiago', 'Soriano', 'Tolentino',
        'Umali', 'Valdez', 'Villanueva', 'Yabut', 'Zamora',
    ];

    private const GUARDIAN_FIRST_NAMES = [
        'Ariela', 'Belen', 'Corina', 'Danilo', 'Elvira', 'Felino', 'Gracia', 'Helena', 'Imelda', 'Junario',
        'Karina', 'Loreta', 'Marina', 'Nerissa', 'Olinda', 'Priscila', 'Ramil', 'Selina', 'Tereso', 'Verina',
        'Apolin', 'Brigida', 'Carmela', 'Delfin', 'Erlinda', 'Florino', 'Gemina', 'Hector', 'Isabelo', 'Jonalyn',
        'Katrina', 'Leonor', 'Merlita', 'Norberto', 'Orestes', 'Paulina', 'Renato', 'Simeona', 'Teresita', 'Virgilio',
        'Arlinda', 'Benicio', 'Celina', 'Dionisio', 'Evelina', 'Francis', 'Gretchen', 'Herminia', 'Isko', 'Julieta',
        'Kristel', 'Ludovico', 'Maribel', 'Nicanor', 'Ofelia', 'Perlita', 'Rosendo', 'Sonia', 'Tomasina', 'Violeta',
        'Anselma', 'Basilio', 'Clarita', 'Dominga', 'Esteban', 'Fidelia', 'Gerardo', 'Honoria', 'Ingrid', 'Jovito',
        'Kendra', 'Lucinda', 'Marlon', 'Nativida', 'Orlando', 'Patricia', 'Rizaldo', 'Solidad', 'Teodoro', 'Vanessa',
        'Aster', 'Benita', 'Cristino', 'Daria', 'Eloisa', 'Fernan', 'Genaro', 'Hilda', 'Irineo', 'Jocelyn',
        'Krizia', 'Lauro', 'Miriam', 'Nestor', 'Omarina', 'Ponciano', 'Rhodora', 'Severino', 'Tina', 'Veron',
    ];

    /**
     * @return array{student_first_name: string, student_middle_name: string, student_last_name: string, guardian_first_name: string}
     */
    public static function studentIdentity(int $index): array
    {
        $firstName = self::STUDENT_FIRST_NAMES[$index % count(self::STUDENT_FIRST_NAMES)];
        $middleName = self::STUDENT_MIDDLE_NAMES[self::permutedIndex($index, count(self::STUDENT_MIDDLE_NAMES), 31, 5)];
        $lastName = self::STUDENT_LAST_NAMES[self::permutedIndex($index, count(self::STUDENT_LAST_NAMES), 37, 11)];
        $guardianFirstName = self::GUARDIAN_FIRST_NAMES[self::permutedIndex($index, count(self::GUARDIAN_FIRST_NAMES), 29, 7)];

        return [
            'student_first_name' => $firstName,
            'student_middle_name' => $middleName,
            'student_last_name' => $lastName,
            'guardian_first_name' => $guardianFirstName,
        ];
    }

    /**
     * @return array{first_name: string, last_name: string}
     */
    public static function teacherIdentity(int $index): array
    {
        $firstName = self::GUARDIAN_FIRST_NAMES[$index % count(self::GUARDIAN_FIRST_NAMES)];
        $lastName = self::STUDENT_LAST_NAMES[self::permutedIndex($index, count(self::STUDENT_LAST_NAMES), 17, 3)];

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    private static function permutedIndex(int $index, int $size, int $multiplier, int $offset): int
    {
        return (($index * $multiplier) + $offset) % $size;
    }
}
