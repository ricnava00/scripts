/*
Decrypts Telegram Android .enc files, given the corresponding .key file
.enc files are from Android/media, .key files are from /data/data
Copy of the telegram source code, decryption is slow for some reason. The sh scripts should be preferred

g++ -Wno-deprecated-declarations TGDecrypt.cpp -lssl -lcrypto -o TGDecrypt
*/

//https://github.com/DrKLO/Telegram/blob/a746a072dce383cc1122fa15244bbcffb33a59d2/TMessagesProj/jni/jni.c#L114
//https://github.com/DrKLO/Telegram/blob/a746a072dce383cc1122fa15244bbcffb33a59d2/TMessagesProj/src/main/java/org/telegram/messenger/secretmedia/EncryptedFileDataSource.java#L70
//https://stackoverflow.com/questions/52369124/what-is-exact-alternate-api-instead-of-aes-ctr128-encrypt-from-openssl-1-1-0
#include <iostream>
#include <tuple>
#include <openssl/aes.h>
#include <openssl/modes.h>
#include <cstdlib>
#include <cstdio>

using namespace std;

int main(int argc, char **argv) {
	unsigned char *bufferBuff;
	unsigned char *keyBuff = (unsigned char *) calloc(1, 32);
	unsigned char *ivBuff = (unsigned char *) calloc(1, 16);

	FILE *fp;
	long length;

	int fileOffset = 0;
	int offset = 0;

	char *infile, *inkeyfile, *outfile;
	if (argc < 4) {
		cout << argv[0] << " infile inkeyfile outfile" << endl;
		return 1;
	}
	infile = argv[1];
	inkeyfile = argv[2];
	outfile = argv[3];
	fp = fopen(infile, "rb");
	if (!fp) {
		cerr << infile << " not found" << endl;
		return 1;
	}

	fseek(fp, 0L, SEEK_END);
	length = ftell(fp);
	rewind(fp);
	bufferBuff = (unsigned char *) calloc(1, length + 1);

	fread(bufferBuff, length, 1, fp);
//	for (int i = 0; i < 8; i++)
//		printf("%02X", bufferBuff[i]);

	fp = fopen(inkeyfile, "rb");
	if (!fp) {
		cerr << inkeyfile << " not found" << endl;
		return 1;
	}
	fread(keyBuff, 32, 1, fp);
	fread(ivBuff, 16, 1, fp);

	AES_KEY akey;
	uint8_t count[16];
	AES_set_encrypt_key(keyBuff, 32 * 8, &akey);
	unsigned int num = (unsigned int) (fileOffset % 16);

	int o = fileOffset / 16;
	ivBuff[15] = (uint8_t)(o & 0xff);
	ivBuff[14] = (uint8_t)((o >> 8) & 0xff);
	ivBuff[13] = (uint8_t)((o >> 16) & 0xff);
	ivBuff[12] = (uint8_t)((o >> 24) & 0xff);
	AES_encrypt(ivBuff, count, &akey);

	o = (fileOffset + 15) / 16;
	ivBuff[15] = (uint8_t)(o & 0xff);
	ivBuff[14] = (uint8_t)((o >> 8) & 0xff);
	ivBuff[13] = (uint8_t)((o >> 16) & 0xff);
	ivBuff[12] = (uint8_t)((o >> 24) & 0xff);

	for (int i = 0; i < 16; i++)
		printf("%02X", ivBuff[i]);
	printf("\n");


	CRYPTO_ctr128_encrypt(bufferBuff + offset, bufferBuff + offset, length, &akey, ivBuff, count, &num, (block128_f) AES_encrypt);
//	for (int i = 0; i < 8; i++)
//		printf("%02X", bufferBuff[i]);
	fp = fopen(outfile, "wb");
	if (!fp) {
		cerr << "Couldn't open output file" << endl;
		return 1;
	}
	fwrite(bufferBuff, length, 1, fp);
	return 0;
}