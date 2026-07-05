# School Form Template Cell Mapping

This file documents fixed cell targets for the current templates in `templates/`.

## Files

- `templates/SF1_2025.xls`
- `templates/SF5.xlsx`
- `templates/SF9.xlsx`
- `templates/SF10.xlsx`

## SF1 (`SF1_2025.xls`)

Sheet: `school_form_1_ver2014.2.1.1`

- Header metadata:
  - `F3` = School ID
  - `T4` = School Year
  - `AE4` = Grade Level
- Learner rows start at row `7`:
  - `A` = LRN
  - `C` = Full Name (Last, First, Middle)
  - `G` = Sex
  - `H` = Birthdate
  - `P` = Address
  - `AB` = Parent/Guardian

## SF5 (`SF5.xlsx`)

Sheet: `school_form_5`

- Header metadata:
  - `E4` = Region
  - `G4` = Division
  - `E5` = School ID
  - `I5` = School Year
  - `L5` = Curriculum
  - `E6` = School Name
  - `L6` = Grade Level
  - `S6` = Section
- Learner rows:
  - Male block starts at row `14`
  - Female block starts at row `58`
  - `A` = LRN
  - `C` = Learner Name
  - `G` = General Average
  - `H` = Action Taken
  - `J` = Learning Areas Not Met

## SF9 (`SF9.xlsx`)

Workbook has two operational sheets:

- `Sheet1` = Learning Progress + Core Values
- `Sheet2` = Attendance + learner header information

### SF9 Sheet1 mapping

- Learner quarterly grades:
  - Learning areas are listed at rows `9` to `19`.
  - Quarter columns are:
    - `D` = Q1
    - `E` = Q2
    - `F` = Q3
    - `G` = Q4
  - Final rating = `H`
  - Remarks = `I`
- General average row:
  - Label at `D24`
  - Computed value target = `H24`

### SF9 Sheet2 mapping

- Learner identity/header:
  - `P24` = Name
  - `P26` = LRN
  - `P28` = Age / Sex
  - `P30` = Grade / Section
  - `P32` = School Year
- Attendance totals:
  - `M` column = total
  - `B6:M6` = month labels
  - `A9`, `A11`, `A14` rows correspond to school days, present, absent labels

## SF10 (`SF10.xlsx`)

Workbook has two main sheets:

- `front` = learner info + 2 yearly scholastic record blocks
- `back` = continuation scholastic record blocks

### SF10 front mapping

- Learner identity:
  - `B7` = Last name field line
  - `F7` = First name field line
  - `L7` = Middle name field line
  - `B8` = LRN field line
  - `G8` = Birthdate field line
  - `L8` = Sex field line
- Scholastic record block #1:
  - Learning area rows `25` to `36`
  - Quarter columns:
    - `G` = Q1
    - `H` = Q2
    - `I` = Q3
    - `J` = Q4
  - `K` = Final rating
  - `L` = Remarks
  - `G39` = General Average
- Scholastic record block #2:
  - Learning area rows `51` to `62`
  - Quarter columns `G:J`
  - `K` = Final rating
  - `L` = Remarks
  - `G65` = General Average

### SF10 back mapping

- Additional scholastic record blocks:
  - Block 3 learning rows `8` to `19`, grades in `F:I`, final `J`, remarks `K`, general average `F21`
  - Block 4 learning rows `32` to `43`, grades in `F:I`, final `J`, remarks `K`, general average `F46`
  - Block 5 learning rows `58` to `69`, grades in `F:I`, final `J`, remarks `K`, general average `F72`

## Notes

- Mapping is strict to these exact templates and sheet names.
- If template versions change, this file must be updated before adjusting exporter code.
