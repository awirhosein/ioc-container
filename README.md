# IoC Container

A lightweight IoC container built from scratch in PHP, as a learning project to deeply understand dependency injection, reflection, and how frameworks like Laravel wire everything together.

## What it does

- Auto-wires classes and their dependencies via PHP Reflection
- Supports bindings, singletons, closure factories, and pre-built instances
- Detects circular dependencies
- Resolves method dependencies via `call()`
- Supports aliases

## Concepts covered

- Dependency Injection & Inversion of Control
- PHP Reflection API (`ReflectionClass`, `ReflectionMethod`)
- Singleton pattern & instance caching
- Test-Driven Development with PHPUnit
