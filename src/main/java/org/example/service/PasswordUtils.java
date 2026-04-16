package org.example.service;

import de.mkammerer.argon2.Argon2;
import de.mkammerer.argon2.Argon2Factory;
import org.mindrot.jbcrypt.BCrypt;

public final class PasswordUtils {
    private PasswordUtils() {
    }

    public static String hashPassword(String plainPassword) {
        return BCrypt.hashpw(plainPassword, BCrypt.gensalt(12));
    }

    public static boolean verifyPassword(String plainPassword, String storedHash) {
        if (plainPassword == null || storedHash == null || storedHash.isBlank()) {
            return false;
        }

        try {
            if (storedHash.startsWith("$2y$") || storedHash.startsWith("$2a$") || storedHash.startsWith("$2b$")) {
                String normalizedHash = storedHash.startsWith("$2y$")
                        ? "$2a$" + storedHash.substring(4)
                        : storedHash;
                return BCrypt.checkpw(plainPassword, normalizedHash);
            }

            if (storedHash.startsWith("$argon2")) {
                Argon2 argon2 = Argon2Factory.create();
                char[] passwordChars = plainPassword.toCharArray();
                try {
                    return argon2.verify(storedHash, passwordChars);
                } finally {
                    argon2.wipeArray(passwordChars);
                }
            }
        } catch (Exception e) {
            return false;
        }

        return plainPassword.equals(storedHash);
    }
}
