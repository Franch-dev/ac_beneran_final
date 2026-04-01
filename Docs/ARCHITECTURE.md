# 🧭 System Architecture

```mermaid
flowchart TD

A[User Browser] --> B[Landing Page]
B --> C[Website Catalog]

C --> D1[AC Service Module]
C --> D2[Inventory Module]
C --> D3[Future Modules]

A --> E[Auth System]

E --> F[Laravel Session / Token]

F --> D1
F --> D2
F --> D3

subgraph Laravel Monolith
    B
    C
    E
    F

    subgraph Modules
        D1
        D2
        D3
    end
end

subgraph Databases
    DB1[(Main DB: Users/Roles)]
    DB2[(AC Service DB)]
    DB3[(Inventory DB)]
end

E --> DB1
D1 --> DB2
D2 --> DB3


---

## ⚡ Optional (Ultra-Light ASCII Version)

```txt
[User]
   |
   v
[Landing Page]
   |
   v
[Website Catalog]
   |       |       |
   v       v       v
[AC]   [Inventory] [Other]

       |
   [Shared Auth]
       |
   [Session/JWT]

Databases:
- Main (Users)
- Module-specific DBs