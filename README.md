# PHP PostgreSQL Investment REST API

A clean, production-ready, lightweight PHP REST API with PostgreSQL for an investment backend. Includes user registration, JWT authentication, balance deposits, balance withdrawals with row-locking concurrency control, and fixed-yield investment management.

## 🚀 Features
- **Authentication**: Bcrypt hashing & RFC 7519 JWT generation.
- **ACID Transactions**: Row-level locks (`SELECT ... FOR UPDATE`) prevent race conditions.
- **Ledger Audit**: Unique references for all funding, withdrawals, and investment flows.
- **Ready for Deployment**: Dockerfile, docker-compose, Render.com blueprint, and Nginx configs included.